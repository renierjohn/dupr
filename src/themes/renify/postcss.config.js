module.exports = {
  map: {
    inline: false, // Set to true if you want the map inside the CSS file
    annotation: true, // Adds a comment to the bottom of the CSS linking the map
    sourcesContent: false // Includes the original source code inside the map
  },
  plugins: [
    require('autoprefixer'),
    require('cssnano')({
      preset: 'default',
    }),
  ]
};
