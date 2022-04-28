# Icon Agency WordPress Vite

Integrates Vite HMR and dist with WordPress.

## Installing

```yml
  "require": {
    "iconagency/wordpress_vite": "^1.0.0",
  },
```

## Add style.css requirements

Add the vite config to your `theme/style.css`

```css
/*
Theme Name:         YourTheme 
Theme URI:          https://your.domain.com/
Description:        YourTheme Wordpress theme
Version:            2.0.0
Author:             You
Author URI:         https://your.domain.com/
License:            MIT License
License URI:        http://opensource.org/licenses/MIT

Vite Client:        http://localhost:8000/@vite/client
Vite Entry:         js/app.js
Vite Dist:          dist/
*/
```

## Example vite config

```js
import { defineConfig } from "vite";

export default ({ mode }) => {
  return defineConfig({
    base: mode === "development" ? "/" : "/app/themes/iconagency/dist/",

    build: {
      manifest: true,
      rollupOptions: {
        input: ["js/app.js"],
        output: { entryFileNames: `[name].js` },
      },
    },

    server: {
      cors: true,
      port: 8000,
      hmr: {
        host: "localhost",
        protocol: "ws",
      },
    },
  });
};
```

## Dev mode

WP_DEBUG should be true to enable the dev server client.
