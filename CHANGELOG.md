# Release Notes

## [Unreleased](https://github.com/surgiie/blade-cli/compare/v3.3.0...master)

## [v3.3.0](https://github.com/surgiie/blade-cli/compare/v3.2.0...v3.3.0) - 2022-11-14
### Changed

- Bring back `runTask` not that flicker is fixed by @surgiie in https://github.com/surgiie/blade-cli/pull/14
## [v3.2.0](https://github.com/surgiie/blade-cli/compare/v3.1.0...v3.2.0) - 2022-11-12
### Added

- First binary build.
## [v3.1.0](https://github.com/surgiie/blade-cli/compare/v3.0.0...v3.1.0) - 2022-11-09
### Changed

- Remove `runTask` usages and update surgiie/console due to overhead and bad flicker from spinner process by @surgiie in https://github.com/surgiie/blade-cli/pull/12

## [v3.0.0](https://github.com/surgiie/blade-cli/compare/v2.0.7...v3.0.0) - 2022-11-08

### Changed

- CLI application has been migrated to  [laravel-zero/laravel-zero](https://github.com/laravel-zero/laravel-zero) by @surgiie in https://github.com/surgiie/blade-cli/pull/10
- Blade classes/componens have been extracted to standalone [surgiie/blade](https://github.com/surgiie/blade) package and direct use of the class is no longer part of this package by @surgiie in https://github.com/surgiie/blade-cli/pull/10
- Validation of path is now handled by `rules` validation via the [surgiie/console](https://github.com/surgiie/console) package by @surgiie in https://github.com/surgiie/blade-cli/pull/10
- `--save-dir` and `--save-as` have been removed in favor of a single `--save-to` option by @surgiie in https://github.com/surgiie/blade-cli/pull/10

### Added
- Application now utilizes [surgiie/console](https://github.com/surgiie/console) package which includes [surgiie/transformer](https://github.com/surgiie/transformer) and laravel validation functionality by @surgiie in https://github.com/surgiie/blade-cli/pull/10

- `--dry-run` now supported on directory rendering by @surgiie in https://github.com/surgiie/blade-cli/pull/10
- `clear` command to clear compiled files by @surgiie in https://github.com/surgiie/blade-cli/pull/10
