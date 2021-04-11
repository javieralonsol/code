'use strict';

const fs = require('fs').promises;
const path = require('path');
const sizeOf = require('image-size');

async function getDir(dirPath, settings = {}, arrayResults = []) {
  // options:
  //   arrayExcludeExtensions
  //   arrayIncludeExtensions
  //   arrayExcludeFiles
  //   arrayIncludeFiles
  //   arrayExcludeFolders
  //   arrayIncludeFolders
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
        if (settings['arrayExcludeFolders']?.includes(element)) {
          return false;
        }

        if (!settings['boolHideEmptyFolders'] || settings['boolOnlyFolders']) {
          arrayResults[newPath] = [];
        }

        if (!settings['boolNotRecursive']) {
          arrayResults = await getDir(newPath, settings, arrayResults);
        }
      } else if (!settings['boolOnlyFolders']) {
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
          if (settings[rule] && settings[rule].some((item) => [rules[rule][0]].includes(item)) === rules[rule][1]) {
            return false;
          }
        }

        arrayResults[dirPath] = arrayResults[dirPath] || [];
        arrayResults[dirPath][element] = {};

        if (settings['boolWithFileInfo']) {
          arrayResults[dirPath][element] = {
            modified: stat.mtime,
            created: stat.ctime,
            filesize: stat.size,
          };
        }

        if (settings['boolWithImageDimensions']) {
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
