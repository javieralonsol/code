<?php

function getDir($dirPath, $options = [], &$arrayResults = []) {
  // $options:
  //   $arrayExcludeExtensions
  //   $arrayIncludeExtensions
  //   $arrayExcludeFiles
  //   $arrayIncludeFiles
  //   arrayExcludeFilesContains
  //   arrayIncludeFilesContains
  //   $arrayExcludeFolders
  //   $arrayIncludeFolders
  //   $arrayExcludeFoldersContains
  //   $arrayIncludeFoldersContains
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
      if (in_array($element, array_merge(['.', '..'], $options['arrayExcludeFolders'] ?? []))) {
        continue;
      }

      if (
        @array_reduce(
          $options['arrayIncludeFoldersContains'],
          function ($acc, $item) use ($element) {
            return $acc && strpos($element, $item) === false;
          },
          true
        ) ||
        @array_reduce(
          $options['arrayExcludeFoldersContains'],
          function ($acc, $item) use ($element) {
            return $acc || strpos($element, $item) !== false;
          },
          false
        )
      ) {
        continue;
      }

      if (empty($options['boolHideEmptyFolders']) || !empty($options['boolOnlyFolders'])) {
        $arrayResults[$newPath] = [];
      }

      if (empty($options['boolNotRecursive'])) {
        getDir($newPath, $options, $arrayResults);
      }
    } elseif (empty($options['boolOnlyFolders'])) {
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
        if (isset($options[$key]) && empty(array_intersect((array) $val[0], $options[$key])) !== $val[1]) {
          continue 2;
        }
      }

      if (
        @array_reduce(
          $options['arrayIncludeFilesContains'],
          function ($acc, $item) use ($element) {
            return $acc && strpos($element, $item) === false;
          },
          true
        ) ||
        @array_reduce(
          $options['arrayExcludeFilesContains'],
          function ($acc, $item) use ($element) {
            return $acc || strpos($element, $item) !== false;
          },
          false
        )
      ) {
        continue;
      }

      $arrayResults[$dirPath][$element] = [];

      if (!empty($options['boolWithFileInfo'])) {
        $arrayResults[$dirPath][$element] += [
          'created' => @filectime($newPath),
          'modified' => @filemtime($newPath),
          'filesize' => @filesize($newPath),
        ];
      }

      if (
        !empty($options['boolWithImageDimensions']) &&
        ([
          0 => $width,
          1 => $height,
          3 => $text,
          'mime' => $type,
        ] = @getimagesize($newPath))
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

if (!array_shift($directCall)) {
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

  echo '<pre>' . (microtime(true) - $microtime) . print_r($arrayDir, true) . '</pre>';
}
?>
