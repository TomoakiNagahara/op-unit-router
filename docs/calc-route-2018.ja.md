# `CalcRoute2018.php` の As-Is

## 対象範囲

この文書は、次の current As-Is 挙動を説明します。

- `asset/unit/router/include/CalcRoute2018.php`

この file は、現在の Router unit が使っている実際の route calculator です。

## 役割

`CalcRoute2018.php` は、現在の request context を受け取り、route table を返します。

出力の形は次です。

```text
{
  "args": [],
  "end-point": "/full/path/to/endpoint"
}
```

この file 自体は endpoint を実行しません。

責務は次の計算で止まります。

- endpoint path
- router 引数

## 入力元

`$request_uri` が空なら、まず次を使います。

- `$_SERVER['REQUEST_URI']`

そして、無ければ次に fallback します。

- `/`

その後、route table を次の初期値で用意します。

- `args = []`
- `end-point = null`

## app root の必須条件

この計算には次が必要です。

- `_ROOT_APP_`

`_ROOT_APP_` が使えない場合、この file は exception を投げます。

## request context の分岐

現在の実装は、request context に応じて挙動を分けます。

### HTTP または CI

次のどちらかが true の場合:

- `OP()->isHttp()`
- `OP()->isCI()`

calculator は HTTP-style routing input として扱います。

この branch では次を行います。

1. request URI から query string を除去する
2. full path を組み立てる

さらにその内側で分岐があります。

- `OP()->isShell()` が true なら `_ROOT_APP_ . $uri`
- そうでなければ `$_SERVER['DOCUMENT_ROOT'] . $uri`

これは、shell からの CI-driven HTTP testing を支える current As-Is 挙動です。

### Shell

request が HTTP でも CI でもない場合は、次を使います。

- `_ROOT_APP_ . ($_SERVER['argv'][1] ?? '')`

つまり、shell mode の route source は app root から見た最初の CLI 引数です。

## path の正規化

full path を組み立てた後、現在の実装は duplicate slash を次で正規化します。

- `str_replace('//', '/', $full_path)`

これは単純な正規化であり、より高度な path canonicalization routine ではありません。

## `asset/` path の制限

計算された full path が次で始まる場合:

- `<app_root>/asset/`

calculator はその対象を次へ書き換えます。

- `<app_root>/404`

つまり、application asset tree の中を通常の routing path で直接 endpoint 解決することは許しません。

## 現在の Pass-Through branch

次に、解決された full path を見ます。

### directory

`is_dir($full_path)` が true の場合、calculator はそこで return しません。

そのまま後段の `index.php` 探索に進みます。

### 既存 file

`file_exists($full_path)` が true の場合、calculator は拡張子を取得し、現在の pass-through 候補と照合します。

- `html`
- `css`
- `js`
- `txt`
- `png`
- `ico`

拡張子が一致した場合、その file 自体が endpoint となり、route table を即 return します。

## 拡張子判定の詳細

現在の実装は、pass-through 判定を文字列検索で行っています。

```php
strpos('html, css, js, txt, png, ico', $extension) !== false
```

つまり、現行 As-Is 実装では、正規化された extension array ではなく string containment を使っています。

この文書では、これは current implementation detail としてだけ記録します。

## [DOC-GAP] Pass-Through 拡張子のハードコード

現在の pass-through 対象拡張子は、`CalcRoute2018.php` の中に直接ハードコードされています。

つまり、現在の routing behavior は、application の設定 policy ではなく、route calculator の source code 編集に依存しています。

これは As-Is の実装詳細であり、framework のより大きな思想を素直に表した形ではありません。

歴史的な HTML Pass-Through の考え方は、すでに HTML だけに閉じない方向へ広がっています。

その観点では、pass-through 対象の集合を calculator source の中に固定するのは本来の思想とずれています。

## [DOC-FUTURE] Pass-Through 拡張子制御の Config 分離

pass-through 対象拡張子は、将来的には config に分離されるべきです。

そうすることで、routing policy は次の点で改善されます。

- 見通しが良くなる
- application ごとに変更しやすくなる
- hard-coded な実装判断から behavior を分離できる

そのため、現在の hard-coded extension string は、現行実装と本来の設計方向のギャップとして理解すべきです。

## 上位への `index.php` 探索

request が pass-through branch で返されない場合、calculator は `index.php` endpoint を探索します。

現在の手順は次です。

1. full path から app root prefix を除去する
2. 末尾 slash を削る
3. 残り path を `/` で分割する
4. 最深部から上位へ向かって `index.php` を探す

例:

```text
/foo/bar/baz
```

探索順:

```text
/foo/bar/baz/index.php
/foo/bar/index.php
/foo/index.php
```

## 引数の組み立て

探索が 1 階層上に戻るたびに、取り除かれた path segment は `args` に追加されます。

現在の実装では次を使います。

- `array_unshift(...)`
- `OP()->Encode($dir)`

つまり次の意味になります。

- 取り除かれた path part は元の順序を保つ
- 各 part は保存前に encode される

## 未解決の場合

一致する `index.php` が見つからなければ、calculator は次の状態で route table を返します。

- `args` は積み上がったまま
- `end-point = null`

この include file は、一般的な未解決 path に対して自動で `404` fallback を強制しません。

単に未解決の route result を返します。

その後どう扱うかは、route table の consumer 側の責務です。

## `Router.class.php` との関係

現在の設計では次の分担です。

- `CalcRoute2018.php` が route 計算を行う
- `Router.class.php` が返された route table を保持・公開する

つまり、この include file が routing engine であり、class 側は route-state holder です。

## フローチャート

### Mermaid

```mermaid
flowchart TD
    A[CalcRoute2018.php 開始] --> B{request_uri は空か}
    B -- yes --> C[request_uri = REQUEST_URI または '/']
    B -- no --> D[与えられた request_uri を使う]
    C --> E[route table を初期化]
    D --> E
    E --> F{_ROOT_APP_ はあるか}
    F -- no --> G[exception を投げる]
    F -- yes --> H{isHttp または isCI か}
    H -- yes --> I[request_uri から query string を除去]
    I --> J{isShell か}
    J -- yes --> K[full_path = app_root + uri]
    J -- no --> L[full_path = DOCUMENT_ROOT + uri]
    H -- no --> M[full_path = app_root + argv1]
    K --> N[// を / に正規化]
    L --> N
    M --> N
    N --> O{full_path は app_root/asset/ で始まるか}
    O -- yes --> P[full_path = app_root + 404]
    O -- no --> Q{is_dir full_path か}
    P --> Q
    Q -- yes --> R[index 探索へ進む]
    Q -- no --> S{file_exists full_path か}
    S -- no --> R
    S -- yes --> T[拡張子を取得]
    T --> U{拡張子は html css js txt png ico に含まれるか}
    U -- yes --> V[route.end-point = full_path]
    V --> W[route table を返す]
    U -- no --> R
    R --> X[app_root prefix を除去]
    X --> Y[末尾 slash を削る]
    Y --> Z[/ で分割して dirs を作る]
    Z --> AA[dir = null]
    AA --> AB[index.php 候補を作る]
    AB --> AC{dir が set されているか}
    AC -- yes --> AD[dir を args に積む]
    AC -- no --> AE[arg 追加なし]
    AD --> AF[full_path = app_root + path]
    AE --> AF
    AF --> AG{file_exists full_path か}
    AG -- yes --> AH[route.end-point = full_path]
    AH --> AI[loop を抜ける]
    AI --> AJ[route table を返す]
    AG -- no --> AK[dir = array_pop dirs]
    AK --> AL{dir は存在するか}
    AL -- yes --> AB
    AL -- no --> AJ
```

### ASCII

```text
開始
  |
  +-- request_uri は空か?
  |     |
  |     +-- yes -> REQUEST_URI または "/"
  |     |
  |     +-- no  -> 渡された request_uri を使う
  |
  +-- route table 初期化
  |     args = []
  |     end-point = null
  |
  +-- _ROOT_APP_ はあるか?
  |     |
  |     +-- no  -> exception
  |     |
  |     +-- yes
  |
  +-- isHttp() または isCI() か?
  |     |
  |     +-- yes
  |     |     |
  |     |     +-- query string を除去
  |     |     +-- isShell() か?
  |     |           |
  |     |           +-- yes -> full_path = app_root + uri
  |     |           +-- no  -> full_path = DOCUMENT_ROOT + uri
  |     |
  |     +-- no
  |           |
  |           +-- full_path = app_root + argv[1]
  |
  +-- duplicate slash を正規化
  |
  +-- full_path は app_root/asset/ で始まるか?
  |     |
  |     +-- yes -> full_path = app_root + "404"
  |     +-- no
  |
  +-- is_dir(full_path) か?
  |     |
  |     +-- yes -> 続行
  |     +-- no
  |           |
  |           +-- file_exists(full_path) か?
  |                 |
  |                 +-- no  -> 続行
  |                 +-- yes
  |                       |
  |                       +-- 拡張子を取得
  |                       +-- pass-through 対象拡張子か?
  |                             |
  |                             +-- yes -> end-point = full_path -> return
  |                             +-- no  -> 続行
  |
  +-- app_root prefix を除去
  +-- 末尾 slash を削る
  +-- path を dirs に分割
  +-- dir = null
  |
  +-- loop
        |
        +-- .../index.php 候補を作る
        +-- dir が set されているか?
        |     |
        |     +-- yes -> Encode(dir) を args 先頭へ積む
        |     +-- no
        |
        +-- full_path = app_root + candidate
        +-- file_exists(full_path) か?
              |
              +-- yes -> end-point = full_path -> break -> return
              +-- no  -> dir = array_pop(dirs)
                            |
                            +-- dir がある -> loop 続行
                            +-- dir がない -> 未解決 route を return
```
