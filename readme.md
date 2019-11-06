# Contacts Sync
A Symfony console application to sync contacts from Planning Center to Google Groups. The application queries both sources for lists named after distribution groups. It then diffs the contacts and makes sure the Google group mirrors the contacts found in Planning Center.

## Installation
All dependencies can be installed using Composer:
```bash
composer install
```

## Configuration
Included in the `config` folder is a `parameters.yml.dist` file. Complete the following steps:
1. Copy this file and rename it `parameters.yml`.
2. Fill in all of the tokens with configuration for Planning Center and Google.
3. Make sure the `lists` parameter is completed with the lists to sync from Planning Center into G Suite.

## Usage

### Sync:Run
To sync contacts between lists, simply run the following command:
```bash
bin/console sync:run
```
This will fetch the lists, run a diff, and display information for changes it is making to the groups.

| Parameter | Description |
| --------- | ----------- |
| --dry-run | Computes the diff and outputs data without actually updating the groups. |