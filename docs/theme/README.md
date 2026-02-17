## Phoenix, a theme by ThemeWagon team.

---

Unzip the **Phoenix-v1.6.0.zip** to any folder and open a command line or terminal at that location. theme's dev tools require [Node](https://nodejs.org/en/) and [Git](https://git-scm.com/) . If you do not have them in your machine, please install their latest stable version from their corresponding website. As you have **Node and Git installed and accessible from your terminal or command line**, install [Gulp CLI](https://gulpjs.com/) package globally with the following command:

```
npm i gulp-cli -g
```

When you’re done, install the rest of the theme’s dependencies with:

```
npm i
```

Now run:

```
gulp
```

Running gulp will compile the SCSS, transpile the javascript, copy all required libraries form `node_modules`
to the corresponding `public/assets/vendors` directory and will open a browser window to `public/index.html`

All of the following folders are monitored for changes, which will tell the browser to reload automatically after any changes are made:

```
public/assets/fonts/
public/assets/video/
public/assets/img/
public/vendors
src/pug/
src/scss/
src/js/
```

Now you can edit any pug file from `src/pug`, change SCSS variable with `scss/\_user-variables.scss`, or write your own SCSS code in `scss/\_user.scss` and add or update javaScript from `src/js` directory.

**Running the gulp command will discard and regenerate all the files in following directories:**

```
public/**/*.html
public/assets/css/
public/assets/js/
public/vendors
```

Hit **Ctrl+C** or just close the command line window to stop the server.

Happy editing!

### Design File

Get the figma design file here:
[https://www.figma.com/design/4kKPdbkTwbEI8Egno57b7H/%F0%9F%94%B4-Phoenix-Distributed-(v1.21.0)](<https://www.figma.com/design/4kKPdbkTwbEI8Egno57b7H/%F0%9F%94%B4-Phoenix-Distributed-(v1.21.0)>)
