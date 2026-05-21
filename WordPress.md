# WordPress.org Distribution


1. Make sure to update the `Stable tag` in the plugin header comment in `performance-toolkit.php` to match the latest release version before tagging a new release on GitHub.
2. Make sure to update the readme.txt file and the latest stable release notes in the changelog.txt file.
3. Verify that the plugin is compatible with the latest version of WordPress and any required dependencies.
4. Test the plugin thoroughly in a staging environment to ensure it functions correctly and meets performance requirements.
5. Make sure to add any additional vendor bundles to the .distignore file in the root of the project to prevent them from being included in the WordPress.org distribution package.
6. Make sure to run `npm run build` before committing changes.
7. Make sure to run `composer install --no-dev --optimize-autoloader` before tagging a new release.


## Github Actions

```
composer install --no-dev --optimize-autoloader
npm ci
npm run build
wp i18n make-pot . languages/performance-toolkit.pot
```
Then deploy to wordpress svn

