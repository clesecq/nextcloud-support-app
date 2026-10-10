# Nextcloud Support app mirror

The `master` branch mirrors the Nextcloud `support` app, one commit per Nextcloud release, tagged with the app version.

This `tooling` branch holds `fetch-support-app.sh`, which downloads new releases from <https://download.nextcloud.com/server/releases/> and commits them. A Forgejo workflow (`.forgejo/workflows/update.yml`) runs it daily and pushes `master` and tags. It needs a `PUSH_TOKEN` secret with repository write access.

Run locally:

```sh
git clone -b master <repo-url> nextcloud/apps/support
./fetch-support-app.sh
```
