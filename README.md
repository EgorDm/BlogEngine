# BlogEngine

BlogEngine is a small and easy to setup code library to create a blog. It contains all the components to create a fully functional blog from a design in a hand twist. Its specially made for the ones who dont want to reinvent the wheel. Just download the library put it in your project and follow the tutorial to set it up.

## Setup
Clone a copy of the main BlogEngine git repo into your project directory by running:
```bash
git clone git://github.com/EgorDm/BlogEngine.git
```
or just download it [here](https://github.com/EgorDm/BlogEngine/archive/master.zip) and place it in your project directory.

Rename file:
```bash
sample-config.ini
```
to
```bash
config.ini
```

Open config.ini with a text editing program and fill in your database settings.

Add this at the top of every page you wnat to use BlogEngine:
```bash
<?php
include_once 'BlogEngine/BEInit.php';
?>
```

You are done! Check this page for the usage. [Click here](https://github.com/EgorDm/BlogEngine/wiki/Usage)

## Contributions
I am happy with every contribution. Please feel free to use, edit and contribute your version of BlogEngine to make it better. You can also helping me by reporting the bugs there are, I will fix them as soon as possible.

## Copyright and license
Copyright 2015 Egor Dmitriev. Released under [the MIT license](https://github.com/EgorDm/BlogEngine/blob/master/LICENSE.md).
