# SOL high テストレビュー

## 対象と判断基準

- 対象: `tests/` 全体（現在の未コミット変更を含む）
- 観点: 1ケース1契約、公開 `Pico` / `Parser` / `ParserResult` 契約中心、テスト名・データが表す契約の正確さ
- 既存の `tests/Unit/PicoTest.php` の未コミット変更は変更していない。
- `Pico::charWhere()` の修正後の4ケースには追加指摘なし。特に成功ケースから predicate 引数の副作用検証を外した変更は、1ケース1契約の方針に合っている。EOF時の「失敗し、predicateを評価しない」は、単一の短絡契約としてまとまっているため問題とは扱わない。

## 指摘（重大度順）

### 1. [高] 共通 matcher が公開 `ParserResult` 契約ではなく具象 `Success` / `Failure` を全テストへ強制している

- file:line: `tests/Pest.php:31`, `tests/Pest.php:34`, `tests/Pest.php:37`
- 問題: `toBeFailure()`, `toBeSuccess()`, `toBeSuccessOf()` が `ParserResult::isFailure()` / `isSuccess()` ではなく `instanceof Failure` / `instanceof Success` を成功条件にしている。`Parser::parse()` の公開戻り値は `Pico\Contracts\ParserResult` であり、具象クラスは契約に含まれていない。
- なぜ害か: 公開契約を満たす別実装へ差し替えるだけで、振る舞いが正しくてもパーサー系テストの大半が壊れる。一方で `isSuccess()` / `isFailure()` 自体の誤実装は、具象型が同じなら各 matcher では検出できない。共通 helper なので影響範囲がテスト全体に及ぶ。
- 最小修正案: matcher は `isSuccess()` / `isFailure()` を直接検証し、成功時のみ `output()` と `consumedLength()` を検証する。`SuccessTest` / `FailureTest` で具象 factory 自体を検証するケースだけ、必要なら局所的に `instanceof` を残す。

### 2. [高] 公開 parser combinator のテストが `PicoInternal` と `parseInput()` に依存している

- file:line: `tests/Unit/Parsers/AbstractParserTest.php:104`, `:116`, `:126`, `:157`, `:167`, `:177`, `:187`, `:197`, `:237`, `:247`, `:262`, `:277`, `:317`, `:339`, `:350`, `:362`, `:380`, `:390`, `:401`
- 問題: `except`, `repeat`, `optional`, `orElse`, `then`, `where` の公開振る舞いを検証するために、明示的な内部名前空間の `Pico\Internal\PicoInternal::asContextualParser()`、`ParserInput`、`parseInput()` を経由している。ほとんどのケースは offset 0 であり、公開 `Parser::parse(string)` だけで同じ契約を表現できる。
- なぜ害か: 利用者から見える結果に変更がなくても、内部 adapter、contextual parser、offset 管理の再編だけで広範囲に壊れる。失敗時 consumed length なども公開 `ParserResult` で検証できるため、内部経路を通す実益がない。テスト名は公開 combinator の契約を示す一方、実際には内部実装との結合を検証してしまっている。
- 最小修正案: 各ケースを `Pico::...()->...()->parse($input)` に置換する。`repeat` の「現在 offset から」というケースだけは、たとえば prefix parser と `then` / `skipLeft` を合成し、公開 API 経由で offset 後の反復を観測する。

### 3. [中] `Pico` factory の公開戻り値より狭い具象クラスを契約として断定している

- file:line: `tests/Unit/PicoTest.php:402`, `tests/Unit/PicoTest.php:660`, `tests/Unit/PicoTest.php:937`
- 問題: `Pico::lazy()`, `Pico::regexp()`, `Pico::whitespaces()` がそれぞれ `LazyParser` / `RegExpParser` のインスタンスを返すことを検証している。しかし3メソッドの公開型はいずれも `Parser` であり、README上の利用契約も parse 結果である。
- なぜ害か: factory の内部実装を別 parser へ置き換える安全なリファクタリングを阻害する。特に `whitespaces()` が regexp 実装であることは「ASCII whitespace を1文字以上 parse する」という公開契約とは無関係。
- 最小修正案: 3つの具象型テストを削除し、直後にある parse 成功・失敗テストへ契約を集約する。factory が `Parser` を返すことを型として確認したい場合も `Parser::class` に留めるが、振る舞いテストが既に十分なため通常は不要。

### 4. [中] `AbstractParser` の公開振る舞いを調べるための無名 parser 実装が不要に多い

- file:line: `tests/Unit/Parsers/AbstractParserTest.php:327`, `:372`, `:421`, `:434`, `:447`, `:465`, `:478`
- 問題: `then` の短絡、`where` の consumed length、`join` の各入力型を検証するために、`AbstractParser` を継承して `parseInput()` を実装した無名クラスを fixture としている。いずれも `Pico::lazy()`, `Pico::string()->map()`, `Pico::seq()` などの公開 parser で同じ入力・出力を作れる。
- なぜ害か: 検証対象の契約に加えて、無名 fixture の `ParserInput` 処理や `success()` helper までテスト成功条件へ混ざる。`AbstractParser` の extension seam を変更したとき、本来無関係な `then` / `where` / `join` 契約のテストまで壊れる。
- 最小修正案: 短絡検証は副作用付き `Pico::lazy()`、2文字消費は `Pico::string('AB')`、任意出力は公開 parser の `map()` で作る。`AbstractParser::parse()` 自体の委譲を隔離している `:23` / `:39` の無名実装は対象メソッドを直接テストするための seam なので、この指摘には含めない。

### 5. [中] 具象 parser のテストが facade から観測できる契約まで `parseInput()` 経由で重複検証している

- file:line: `tests/Unit/Parsers/LazyParserTest.php:17`, `:20`, `:39`, `:54`; `tests/Unit/Parsers/RecursiveParserTest.php:18`, `:21`, `:29`, `:48`, `:62`, `:75`; `tests/Unit/Parsers/RegExpParserTest.php:16`, `:19`, `:27`, `:35`, `:43`, `:54`, `:68`
- 問題: `LazyParser`, `RecursiveParser`, `RegExpParser` を直接構築し、内部文脈用の `parseInput(ParserInput)` で検証している。遅延評価・definition の一度きりの評価・再帰・regexp のエラーは、`Pico::lazy()`, `Pico::recursive()`, `Pico::regexp()` と公開 `parse()` から同じように観測できる。
- なぜ害か: facade の契約テストと実装クラスのテストが二重化し、constructor や contextual parsing の再編で一斉に壊れる。利用者が依存しない `ParserInput` の生成方法まで事実上の互換契約にしてしまう。
- 最小修正案: 振る舞いケースは `Pico` factory + `parse()` へ移す。regexp の非zero offset は prefix parser との合成で表現する。もし具象クラスを正式な extension API とする意図があるなら、その範囲を公開ドキュメントへ明記した上で、facade 契約とは別の implementation test として限定する。

### 6. [中] `Pico::seq()` の describe 内に `optional()` 単体の契約が混入している

- file:line: `tests/Unit/PicoTest.php:784`
- 問題: テストは `Pico::char('A')->optional()->parse('BC')` だけを実行しており、`Pico::seq()` を一度も使わない。にもかかわらず `describe('Pico::seq()')` 内に置かれている。
- なぜ害か: レポート上は `seq` の保証に見える一方、実際には optional の非一致契約しか検証していないため、契約の所在を誤解させる。さらに直前の `:776` が `seq` 内の zero-length output 保持を既に検証しており、このケースを `seq` に残す理由がない。
- 最小修正案: `AbstractParser::optional` の describe へ移す。`seq` の意図だったなら、必ず `Pico::seq(...)` を通すケースへ変更するが、`:776` と重複するなら削除する。

### 7. [中] failed recursive definition の1ケースが別々のエラー契約を同時に担っている

- file:line: `tests/Unit/Parsers/RecursiveParserTest.php:65`
- 問題: `should not reevaluate a failed definition` の1ケースで、(a) 初回の definition 例外をそのまま伝播すること、(b) 2回目は別の `ParserException` になること、(c) definition 呼び出し回数が1であること、を同時検証している。テスト名が直接表すのは (c) で、(a) は独立した例外伝播契約である。
- なぜ害か: 初回例外の型・メッセージを変える変更と、再評価防止の回帰が同じ失敗として現れ、原因が特定しにくい。テスト名から初回例外伝播まで保証されていることも読み取れない。
- 最小修正案: 「failed definition の例外を伝播する」ケースと「失敗後に definition を再評価しない」ケースへ分ける。後者の初回失敗は Arrange として捕捉し、2回目の結果と call count だけを Assert する。

### 8. [低] factory テスト名の “new instance” を assertion が保証していない

- file:line: `tests/Unit/FailureTest.php:13`, `tests/Unit/SuccessTest.php:12`
- 問題: どちらも「new instance を返す」と命名されているが、1回だけ呼び出してクラス型を確認しているため、新規性（呼び出しごとに別 identity）を検証していない。
- なぜ害か: 将来 singleton / cache 化されてもテストは通り続け、名前だけが存在しない契約を保証しているように見える。
- 最小修正案: identity の新規性が契約でないなら `should return a Failure/Success instance` へ改名する。新規性が契約なら2回生成し、同一 instance でないことを専用ケースで検証する。

## 指摘しなかった慣行

- `toBeSuccessOf(output, consumedLength)` で output と consumed length を同時に確認することは、parse 結果という1つの値契約として扱った。
- 短絡ケースで parse 結果と callback 未実行を併せて確認することは、同じ制御フロー契約の原因と結果であるため、機械的には分割対象にしていない。
- dataset で成功・失敗の同一契約を複数入力へ適用することは、1ケース1契約に反しないため指摘していない。

## 結論

重大な論点は、共通 matcher と `AbstractParserTest` が公開インターフェースより具象・内部経路を強く固定していること。そこを先に公開 `ParserResult` / `Parser::parse()` ベースへ直すと、残る指摘の多くも自然に整理できる。
