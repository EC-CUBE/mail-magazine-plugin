# AGENTS.md

This file provides guidance to Codex (Codex.ai/code) when working with code in this repository.

## このリポジトリについて

EC-CUBE 4 系の**メルマガ管理プラグイン**。配信先の絞り込み、テンプレート管理、メルマガ配信・履歴管理を管理画面から行える。

- 管理画面: 「メルマガ管理」（`Controller/MailMagazineController.php`、`Controller/MailMagazineHistoryController.php`、`Controller/MailMagazineTemplateController.php`）

プラグインコードは `MailMagazine44`、Composer パッケージ名は `ec-cube/MailMagazine44`。コード中の Twig 名前空間（`@MailMagazine44`）・クラス名前空間（`Plugin\MailMagazine44\...`）はすべて `MailMagazine44` 接頭辞を使う。

### ブランチ運用

ブランチ名が対応する EC-CUBE 本体バージョンを表す（`4.0` / `4.2` / `4.4` など）。`4.2` がデフォルトブランチ。`4.2` ブランチは EC-CUBE 4.2/4.3（`MailMagazine42`）に対応し、`4.4` ブランチは EC-CUBE 4.4（Symfony 7.4 / Doctrine ORM 3.0 / PHP 8.2+、`MailMagazine44`）に対応する。**4.3 と 4.4 はアノテーション必須/属性必須・ORM 2/3 の違いで非互換**のため、別ブランチで保守する。

## 開発・テストコマンド

このプラグイン単体では動作せず、**EC-CUBE 本体に組み込んだ状態**で開発・テストする。本体の取得・インストール・プラグイン有効化は `docker-compose.dev.yml` の entrypoint が自動実行する。

```bash
# 開発環境 (SQLite) の起動 — 本体インストール・プラグイン有効化まで自動
export COMPOSE_FILE=docker-compose.yml:docker-compose.dev.yml
docker compose up -d --wait

# MySQL / PostgreSQL で起動する場合
export COMPOSE_FILE=docker-compose.yml:docker-compose.mysql.yml:docker-compose.dev.yml
export COMPOSE_FILE=docker-compose.yml:docker-compose.pgsql.yml:docker-compose.dev.yml

# PHP バージョン切り替え（8.2-apache-4.4 / 8.3-apache-4.4 / 8.4-apache-4.4 / 8.5-apache-4.4）
TAG=8.3-apache-4.4 docker compose up -d --wait
```

起動後は管理画面 `https://localhost:8080/4430`（`admin` / `password`）、メールは MailCatcher `http://localhost:1080`。

### PHPUnit

テストは `Tests/` 配下の PHPUnit（`Tests/Repository/`・`Tests/Web/`）。`phpunit.xml.dist` により `APP_ENV=test` で実行される。

```bash
docker compose exec ec-cube bash -lc \
  "export APP_ENV=test && bin/console cache:clear --no-warmup && ./vendor/bin/phpunit -c app/Plugin/MailMagazine44/phpunit.xml.dist app/Plugin/MailMagazine44/Tests"
```

**注意（コンパイル済みキャッシュ）**: 有効化したプラグインのルーティングは、コンテナのコンパイル時に `dtb_plugin` を読む `EccubeExtension` で確定する。有効化直後の test キャッシュには反映されていないことがあるため、**PHPUnit 実行前に `APP_ENV=test` でキャッシュをクリアする**。これを怠るとコントローラのルートが `RouteNotFoundException` になる。

### 静的解析・整形（任意）

EC-CUBE 本体（コンテナ内）の vendor を使って実行する。

```bash
# php-cs-fixer
docker compose exec ec-cube bash -lc \
  "cd app/Plugin/MailMagazine44 && /var/www/html/vendor/bin/php-cs-fixer fix --config=Resource/.php-cs-fixer.dist.php --dry-run --diff"

# rector（再移行・検証用）
docker compose exec ec-cube bash -lc \
  "cd app/Plugin/MailMagazine44 && /var/www/html/vendor/bin/rector process --config=Resource/rector.php --dry-run"

# phpstan
docker compose exec ec-cube bash -lc \
  "cd app/Plugin/MailMagazine44 && /var/www/html/vendor/bin/phpstan analyse"
```

phpstan は level 6 で **baseline なし・エラーゼロ**。Repository は `@extends AbstractRepository<MailMagazineSendHistory>` 等を付与して `find()` 等の戻り値型を確定させている。新規コードもこの水準を維持すること。

## アーキテクチャ

- **Entity** (`Entity/MailMagazineSendHistory.php`, `Entity/MailMagazineTemplate.php`): 配信履歴・テンプレート。`#[ORM\*]` 属性 + 型付きプロパティ。`Entity/CustomerTrait.php` は `#[EntityExtension]` で `Customer` にメルマガ受信フラグを追加。
- **Controller** (`Controller/`): `#[Route]` 属性。配信先検索・内容設定・テンプレート管理・配信履歴。
- **Form** (`Form/Type/`, `Form/Extension/`): 配信条件・テンプレート編集フォーム。会員登録/管理画面/マイページ向け FormExtension。
- **Repository** (`Repository/`): 履歴・テンプレートの検索・永続化。
- **Service** (`Service/MailMagazineService.php`): 配信ファイル/結果ファイル管理、メール送信、履歴作成。
- **Event** (`Event/MailMagazineHistoryFilePaginationSubscriber.php`): 配信結果 CSV のページネーション。
- **PluginManager** (`PluginManager.php`): アンインストール時に `mail_magazine_dir` を削除。全メソッド `: void`。
- **Nav** (`MailMagazineNav.php`): 管理画面メニューにメルマガ管理を追加。

## 規約・移行メモ

### 開発ツール設定ファイルは `Resource/` 配下に置く（rector.php / .php-cs-fixer.dist.php）

`rector.php` や `.php-cs-fixer.dist.php` を**プラグインのルート直下に置いてはならない**。`Resource/` 配下に置く。

**理由**: EC-CUBE 本体の `config/eccube/services.yaml` がプラグインを丸ごと PSR-4 サービス検出対象として読み込む:

```yaml
Plugin\:
    resource: '../../../app/Plugin/*'
    exclude: '../../../app/Plugin/*/{Entity,Resource,ServiceProvider,Tests,Codeception,DoctrineMigrations}'
```

ルート直下の `*.php` は「サービスクラス」として読み込まれるため、`rector.php` を置くと Symfony が `Plugin\MailMagazine44\rector` クラスを期待し、見つからず **EC-CUBE 全体が 500 エラー**になる。`exclude` に `Resource` が含まれるため `Resource/` 配下なら衝突しない。`phpstan.neon.dist` は `.php` ではないためルートに置ける。

**将来「本体に合わせてルートへ戻す」とリグレッションするため、この配置を変更しないこと。**

### docker 環境は `APP_ENV=dev` で起動する

ブラウザログインには実セッション（`session.storage.factory.native`）が必要。`APP_ENV=test` ではモックストレージ（`mock_file`）になりログインできない。また EC-CUBE 4.4（Symfony 7）は既定 `cookie_samesite: none` のため、HTTP 環境では `dockerbuild/dev-framework.yaml`（`cookie_secure:false` / `cookie_samesite:lax`）を `app/config/eccube/packages/dev/framework.yaml` に重ねて回避している。

### 有効化後は `cache:clear` を 2 回流す（ルート/Nav の確定）

`eccube:plugin:enable` 直後の 1 回の `cache:clear` だけでは、プラグインのルーティング（メルマガ管理 `plugin_mail_magazine`）と Nav メニューが確定せず、**初回ロードで `/admin/plugin/mail_magazine` が 404** になることがある。enable とは別パスで `cache:clear` をもう一度実行すると確定する（`enable` が内部で行うキャッシュ再生成と競合するためと見られる）。そのため `docker-compose.dev.yml` の entrypoint は有効化後に `bin/console cache:clear` を **2 回** 実行する。手動でプラグインを再有効化した場合も同様に行うこと。

### プラグインの導入方法（tar + plugin:install）

`docker-compose.dev.yml` はマウントしたプラグインを `./*` で tar 化し `eccube:plugin:install --path` で導入する。`eccube:composer:require` はパッケージ API（`extra.id`）を要求するため path プラグインでは使えない。また **`PharData` は先頭の `./` エントリで展開に失敗する**ため、プラグインディレクトリ内で `./*` を対象に tar 化する（`-C dir .` は不可）。