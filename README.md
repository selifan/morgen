# Morgen - Mobile Resource Generating

## Short description

Android and iOS developers know that designing one icon set for their application is not enough:
because of many device types ans screen sizes they have to create resized pictire collections for many "resolutions".
They should be placed in "resources" subfolders according to device types/display resolutions.
Android Studio has a special tools for dealing with them (Android media tools),
some people use Photoshop/Illustrator scripts that automate creating icon sets for all resolutions.

Here is a PHP solution for that. It can make resized versions for JPG, PNG, GIF source files.

If you have installed some SVG-to-PNG converter program (inkscape for example) that can work with command line parameters,
Morgen can convert SVG files too (resized versions will be of PNG type).


## Simple Using example

```php
include_once('src/morgen.php');

include_once('morgen.php');

$generator = new \Morgen\IconGenerator();

// show full cmd line for your inkscape installation:
$generator->setSvgConvertor('D:/inkscape/inkscape.exe -z {from} -e {to}');

$options = array(
  'project' => 'project-greatApp.xml',
  'forced'  => false
);

$generator->createIconsFromImages($options);
```

Morgen can create all icon sets from a single media pack for all defined projects:
for android version, for ios version, for web application etc.
All you need is defining all "application types" in XML configuration file.

You will find "default" configuration file morgen.cfg.xml, that contains three "predefined" profiles (application types) for generated pictures:
"android", "ios" and "webapp" (webapp is just a sample)

To change them or add your own ones, edit the global in morgen.cfg.xml -
it should be placed in the same folder with morgen.php module.

See a [wiki](https://github.com/selifan/morgen/wiki) for using details.
## License
Distributed under MIT License
[MIT](https://opensource.org/licenses/MIT)
