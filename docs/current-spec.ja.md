# Router の現行仕様

## 概要

現在の `op-unit-router` 実装は、request URL から実行すべき endpoint を決定します。

class 単体で見ると、`Router.class.php` 自体は非常に小さいです。

class の中に routing algorithm 全体を直接持っているわけではありません。

代わりに、次で計算された route table を初期化して保持します。

- `asset/unit/router/include/CalcRoute2018.php`

Router の役割は、次の 2 つを持つ route result を構築することです。

- `args`
- `end-point`

Router 自体はレスポンスを描画しません。次に何を実行すべきかを決める役割です。

## 責務の境界

Router unit の責務は次です。

- endpoint を解決する
- router 引数を解決する
- 次に何を実行すべきかを決める

Router unit の責務ではないものは次です。

- endpoint 自体を実行すること
- output を buffer すること
- layout を適用すること
- 最終的な HTML wrapper を描画すること

class 境界で言うと、`Router.class.php` 自体の責務は次です。

- 現行の route calculation include を読み込むこと
- 計算済み route table を `$this->_route` に保持すること
- `EndPoint()`, `Args()`, `Table()` で保持済みの値を公開すること

実際の path 計算 logic は `CalcRoute2018.php` 側にあります。

現在の route calculator の詳細な As-Is 挙動は次を参照して下さい。

- `calc-route-2018.md`

## 現在の class の形

現在の `Router.class.php` は、実質的に route calculation include の薄い wrapper です。

public surface はかなり小さく、次だけです。

- `__construct()`
- `EndPoint()`
- `Args()`
- `Table()`

constructor は次を実行します。

- `include(__DIR__.'/include/CalcRoute2018.php')`

そして返ってきた route table を保持します。

その後の getter 群は、保持済み table から値を返すだけです。

つまり、現行 As-Is では次の分担です。

- `Router.class.php` は route table の holder
- `CalcRoute2018.php` は実際の route calculator


## Route Result

Router は次の構造を持つ route table を返します。

```text
{
  "args": [],
  "end-point": "/full/path/to/endpoint"
}
```

## Request の取得元

現在の実装では、次を使います。

- HTTP request では `$_SERVER['REQUEST_URI']`
- shell 利用では `$_SERVER['argv'][1]`

その後、request を application root 配下の full path に変換します。

## asset path の制限

解決された path が `asset/` を指す場合、Router はそれを通常の endpoint path としては扱いません。

代わりに、その対象を `404` path に内部的に置き換えます。

これにより、application asset 領域の中を通常 endpoint として直接解決しないようにしています。

## 現在の Pass-Through 挙動

解決された request path がすでに存在する file である場合、Router はその拡張子を確認します。

現在の実装で pass-through 候補として扱っている拡張子は次です。

- `html`
- `css`
- `js`
- `txt`
- `png`
- `ico`

その file が存在し、かつ拡張子がこの一覧に一致する場合、Router はその file 自体を endpoint として返します。

この場合、別の controller 的な `index.php` を探すのではなく、その file 自体が framework フローの中で直接実行または処理されます。

## `index.php` 解決の挙動

request が pass-through path として返されない場合、Router は `index.php` の endpoint を探索します。

探索は request path から上位階層へ向かって行われます。

例:

```text
/foo/bar/baz
```

Router は概ね次の順で探索します。

```text
/foo/bar/baz/index.php
/foo/bar/index.php
/foo/index.php
```

1 階層上に戻るたびに、取り除かれた path segment は `args` に追加されます。

その結果、

- 最も近い既存の `index.php` が endpoint になる
- 余った path 部分が router 引数になる

という動作になります。

## Directory と File の扱い

現在の挙動は次のように要約できます。

- 既存の pass-through file -> その file 自体が endpoint になる
- pass-through ではない path -> 最も近い `index.php` を探索する
- `asset/` path -> 内部的に `404` へ振り替える

## 現在の Framework フローとの関係

Router が endpoint を返した後は、次の流れになります。

1. App unit が endpoint を受け取る
2. `OP()->Template()` によって endpoint を実行する
3. 出力を保持する
4. layout を適用するかどうかを framework が判断する

## 注意

この文書は `op-unit-router` の **現行実装** を説明するものです。

名称、文書、実装が完全に一致していることを意味するものではありません。

特に、歴史的な用語である `HTML Pass-Through` は、現在 Router が扱っている拡張子一覧よりも狭い表現です。

## [DOC-GAP] Pass-Through 拡張子 policy

現在の pass-through 対象拡張子一覧は、route calculator の中にハードコードされています。

これは現行実装上の都合であり、長期的に望ましい policy 境界ではありません。

本来、対象拡張子の policy は `CalcRoute2018.php` の中ではなく config 側に属するべきです。

## 小さな class であることの意味

重要な実装上の特徴は、`Router.class.php` が意図的に小さく保たれていることです。

この class は次を自分で抱え込みません。

- path normalization logic
- pass-through detection logic
- index search logic
- argument extraction logic

これらの挙動は、現行では include された route calculator file にあります。

そのため、現在の Router class は、大きな self-contained routing engine class というよりも、次のように理解するのが正確です。

- routing state の holder
- route result への public access point
