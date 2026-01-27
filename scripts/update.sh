#!/bin/bash
bash scripts/composer.sh install
bash scripts/drush.sh updb -y
bash scripts/drush.sh cim -y
bash scripts/drush.sh cr
bash scripts/drush.sh uli
