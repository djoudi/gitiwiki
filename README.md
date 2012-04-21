
# GitiWiki

This web application will be a wiki system, storing pages into a Git repository.

No release yet. Work in progress.

## features

This project is new, and for the moment, it only reads and display content from a Git repository.
Features to create and modify wiki page with the browser will be provided later (Contributions are welcomed).

Main existing features:

- Support of the Dokuwiki syntax and extended tags (support of others wiki syntaxes is planned of course);
- user protocols for links: you can define "protocols" for urls to have aliases to real urls;
- store anything in your repository and where you want: images, pdf, xml files etc.. ;
- hidden files: files or directory begining by a dot are not accessible with a browser;
- support of several syntax or type file: assign a rendering engine to specific file extensions;
- multiviews: in an URL, the extension part of a filename is not required, Gitiwiki will find the right file.
  So later you can modify the extension (and so the wiki syntax for example) without modifying URL in other files;
- support of redirections: you rename a file or move your page, even to an other site? Indicate it to Gitiwiki.
- support of books: define a page including a summary, the files list of your book, and Gitiwiki adds
navigation bar automatically on web pages. In the future, you could also generate PDF.

A demo ? Go to the web site of [Jelix manuals](http://docs.jelix.org/en) to see Gitiwiki in action.

## Documentation

- [Installation](./docs/installation.md)
- [Adding a repository](./docs/repository.md) and what it should contain.
- [URL in wiki content](./docs/url-support.md)
- [Wiki syntax](./docs/syntax.md)
- [Writing books](./docs/books)

## Design

For the moment, Gitiwiki doesn't really provide a design (but it's planned of course).
Since Gitiwiki is a Jelix application, to have your own design, simply
[follow instructions](http://docs.jelix.org/en/manual-1.3/themes)
to create a new theme, in the Jelix documentation.

Just copy the file `gitiwiki/modules/gitiwiki/templates/main.tpl` to 
`gitiwiki/var/themes/default/gitiwiki/main.tpl` and modify it. It must contains only
HTML content of the `<body>` element. If you want to add style sheets or javascript,
add these kind of tags at the begining of your templates:

```
    {meta_html css '/mystyles/my.css'}
    {meta_html js '/myscripts/fooscript.js'}
```

Store these CSS/js files into `gitiwiki/www/mystyles/my.css` and `gitiwiki/www/myscripts/fooscript.js`.

