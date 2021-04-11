<?php

function getDir($dirPath, $optionss = [], &$arrayResults = []) {
  // $options:
  //   $arrayExcludeExtensions
  //   $arrayIncludeExtensions
  //   $arrayExcludeFiles
  //   $arrayIncludeFiles
  //   $arrayExcludeFolders
  //   $arrayIncludeFolders
  //   $arrayExcludeParentFolders
  //   $arrayIncludeParentFolders
  //   $boolHideEmptyFolders
  //   $boolNotRecursive
  //   $boolOnlyFolders
  //   $boolWithFileInfo
  //   $boolWithImageDimensions

  $arrayScan = @scandir($dirPath);
  if ($arrayScan === false) {
    return false;
  }

  foreach ($arrayScan as $element) {
    $newPath = $dirPath . DIRECTORY_SEPARATOR . $element;

    if (is_dir($newPath)) {
      if (in_array(
        $element,
        array_merge(['.', '..'],
        $optionss['arrayExcludeFolders'] ?? []))) {
        continue;
      }

      if (
        empty($optionss['boolHideEmptyFolders']) ||
        !empty($optionss['boolOnlyFolders'])) {
        $arrayResults[($newPath)] = [];
      }

      if (empty($optionss['boolNotRecursive'])) {
        getDir($newPath, $optionss, $arrayResults);
      }
    } elseif (empty($optionss['boolOnlyFolders'])) {
      $elementExtension = strtolower(pathinfo($element, PATHINFO_EXTENSION));
      $basename = basename($dirPath);

      $rules = [
        'arrayExcludeExtensions' => [$elementExtension, true],
        'arrayIncludeExtensions' => [$elementExtension, false],
        'arrayExcludeFiles' => [$element, true],
        'arrayIncludeFiles' => [$element, false],
        'arrayExcludeParentFolders' => [$basename, true],
        'arrayIncludeParentFolders' => [$basename, false],
        'arrayIncludeFolders' => [explode(DIRECTORY_SEPARATOR, $dirPath), false],
      ];

      foreach ($rules as $key => $val) {
        if (isset($optionss[$key]) && empty(array_intersect((array) $val[0], $optionss[$key])) !== $val[1]) {
          continue 2;
        }
      }

      $arrayResults[$dirPath][$element] = [];

      if (!empty($optionss['boolWithFileInfo'])) {
        $arrayResults[$dirPath][$element] += [
          'created' => @filectime($newPath),
          'modified' => @filemtime($newPath),
          'filesize' => @filesize($newPath),
        ];
      }

      if (
        !empty($optionss['boolWithImageDimensions']) &&
        ([0 => $width, 1 => $height, 3 => $text, 'mime' => $type] = @getimagesize($newPath))
      ) {
        $arrayResults[$dirPath][$element] += [
          'height' => $height,
          'width' => $width,
          'text' => $text,
          'type' => $type,
        ];
      }
    }
  }

  return $arrayResults;
}

// si es una llamada directa (no a traves de un include)
$directCall = debug_backtrace();

if(!array_shift($directCall)){
  header('Content-Type: text/html; charset=utf-8');
  ini_set('default_charset', 'utf-8');
  error_reporting(E_ALL);
  set_time_limit(0);
  ob_implicit_flush(true);

  $microtime = microtime(true);

  $arrayDir = getDir('/home/user/www/media', [
    'arrayIncludeExtensions' => ['jpg', 'jpeg', 'webp'],
    'boolHideEmptyFolders' => true,
    'boolWithFileInfo' => true,
    'boolWithImageDimensions' => true,
  ]);

  echo '<pre>' . (microtime(true) - $microtime) .  print_r($arrayDir, true) . '</pre>';
}
?>