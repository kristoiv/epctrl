# EpCtrl.com (Episode Control)

Official service url: [http://epctrl.com](http://epctrl.com).

Episode Control is a simple service based on epguides.com's open database of tv-series. The service allows you to manage a list of your favourite tv-series, you check off any episodes you've viewed and get a overview of the next available episodes.

## Requirements
If you'd like to fork this project there are a few steps to making it operational.
* You need a working **LAMP-stack** (wmap / mamp might work too).
* You need a empty **database** + user with **all privileges**.

## Getting started
* Clone this repository
* Copy ```phinx.yml.example``` to ```phinx.yml``` and edit it to match your database credentials.
* Copy ```application/configs/application.ini.example``` to ```application/configs/application.ini``` and edit it to match you details.
* Migrate the database by running the following command: ```./vendor/robmorgan/phinx/bin/phinx migrate```

## License
Copyright (C) 2013 Kristoffer A. Iversen

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
