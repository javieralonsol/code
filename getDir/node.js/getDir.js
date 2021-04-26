'use strict';

const fs = require('fs').promises;
const path = require('path');
const sizeOf = require('image-size');

async function getDir(dirPath, options = {}, arrayResults = []) {
  // options:
  //   arrayExcludeExtensions
  //   arrayIncludeExtensions
  //   arrayExcludeFiles
  //   arrayIncludeFiles
  //   arrayExcludeFilesContains
  //   arrayIncludeFilesContains
  //   arrayExcludeFolders
  //   arrayIncludeFolders
  //   arrayExcludeFoldersContains
  //   arrayIncludeFoldersContains
  //   arrayExcludeParentFolders
  //   arrayIncludeParentFolders
  //   boolHideEmptyFolders
  //   boolNotRecursive
  //   boolOnlyFolders
  //   boolWithFileInfo
  //   boolWithImageDimensions

  let arrayScan = [];
  try {
    arrayScan = await fs.readdir(dirPath);
  } catch (error) {
    return false;
  }

  const promises = arrayScan.map(async (element) => {
    const newPath = path.join(dirPath, element);
    try {
      const stat = await fs.stat(newPath);

      if (stat.isDirectory()) {
        if (options['arrayExcludeFolders']?.includes(element)) {
          return false;
        }

        if (
          options['arrayIncludeFoldersContains']?.every((item) => !element.includes(item)) ||
          options['arrayExcludeFoldersContains']?.some((item) => element.includes(item))
        ) {
          return false;
        }

        if (!options['boolHideEmptyFolders'] || options['boolOnlyFolders']) {
          arrayResults[newPath] = [];
        }

        if (!options['boolNotRecursive']) {
          arrayResults = await getDir(newPath, options, arrayResults);
        }
      } else if (!options['boolOnlyFolders']) {
        const elementExtension = element.split('.').pop();
        const baseName = path.basename(dirPath);

        const rules = {
          arrayExcludeExtensions: [elementExtension, true],
          arrayIncludeExtensions: [elementExtension, false],
          arrayExcludeFiles: [element, true],
          arrayIncludeFiles: [element, false],
          arrayExcludeParentFolders: [baseName, true],
          arrayIncludeParentFolders: [baseName, false],
          arrayIncludeFolders: [dirPath.split(path.sep), false],
        };

        for (const rule in rules) {
          if (options[rule] && options[rule].some((item) => [rules[rule][0]].includes(item)) === rules[rule][1]) {
            return false;
          }
        }

        if (
          options['arrayIncludeFilesContains']?.every((item) => !element.includes(item)) ||
          options['arrayExcludeFilesContains']?.some((item) => element.includes(item))
        ) {
          return false;
        }

        arrayResults[dirPath] = arrayResults[dirPath] || [];
        arrayResults[dirPath][element] = {};

        if (options['boolWithFileInfo']) {
          arrayResults[dirPath][element] = {
            modified: stat.mtime,
            created: stat.ctime,
            filesize: stat.size,
          };
        }

        if (options['boolWithImageDimensions']) {
          try {
            arrayResults[dirPath][element] = { ...arrayResults[dirPath][element], ...sizeOf(newPath) };
          } catch (error) {}
        }
      }
    } catch (error) {}
  });

  return await Promise.all(promises).then(() => arrayResults);
}

console.time('getDir');

getDir('/home/user/www/media', {
  arrayIncludeExtensions: ['jpg', 'jpeg', 'webp'],
  boolHideEmptyFolders: true,
  boolWithFileInfo: true,
  boolWithImageDimensions: true,
}).then((res) => {
  console.timeEnd('getDir');
  console.log(res);
});
