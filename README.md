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

## Project definition
Beside a main configuration, you have to prepare XML file that describes your project (possibly multi-target: isom android, webapp)
It's name is up to you, just use it when you call createIconsFromImages() method.
Let's assume we're developing a project called geratApp, so we will create XML definition for it in greatApp.xml:
```xml
<?xml version="1.0" encoding="UTF-8"?>
<!-- "greatApp" project definition file -->
<projectDef>

  <project name="greatApp" sourceFolder="media/">

     <branch type="android" destinationFolder="android/greatApp/resources/">
     </branch>

     <branch type="ios" destinationFolder="ios/greatApp/resources/">
       <images>
         <image mask="*.svg" devices="iphone,iphone-2x" />
       </images>
     </branch>

  </project>

</projectDef>
```
Here we tell that we're developping our application for android and ios, all source image files located in "media/" folder.
For "android" project we want to create sets for all registered screen/device resolutions (because we didn't specify "image" tag with "devices" attribute)
For "ios" we want create images just from "svg" source files and only for two resolutions - "iphone" and "iphone-2x".
It is possible to set many "image" block in one "images" branch, specifying separated parameters for different files

## License
Distributed under MIT Licenses
[MIT](https://opensource.org/licenses/MIT)
