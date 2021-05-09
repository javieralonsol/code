let myArr = [];
document.addEventListener('dblclick', (event) => {
  [...document.querySelectorAll('[role=presentation] img[sizes]')].map((el) => {
    const temp = el.srcset.split(',').pop().split(' ').shift();
    myArr[temp] = 1;
  });
});

document.addEventListener('contextmenu', (event) => {
  for (const f in myArr) {
    document.write(`<img src="${f}" style="width:150px" />`);
  }
});

// código minificado
// minified code
myArr = []; document.addEventListener('click', event => [...document.querySelectorAll('[role=presentation] img[sizes]')].map(el => {temp = el.srcset.split(',').pop().split(' ').shift(); myArr[temp] = 1;})); document.addEventListener('contextmenu', event => {for (f in myArr) document.write(`<img src="${f}" style="width:150px" />`);});