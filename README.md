
# GitiWiki

This will be a wiki system, storing pages into a Git repository.

For the moment, it only reads and display content from a repository. It supports
the dokuwiki syntax, hidden files, multiviews and redirections.

No release yet. Work in progress.


## Installation

Gitiwiki is a PHP application, using the framework [Jelix](http://jelix.org), and
the [Glip library](https://github.com/patrikf/glip) to read the content of a git
repository.

WARNING: You will probably think that the installation is a bit complicated for
a non-developer, but don't worry! For the moment, GitiWiki targets only developers
who want to contribute to the project. The first release will contain of course a
wizard which will simplify everything!

Here are steps:

1. checkout the source code with `git clone`
2. put "write" rights for your web server on these directories
  - temp/gitiwiki
  - gitiwiki/var/log/
  - gitiwiki/var/books/
3. configure a virtual host with `gitiwiki/www` as a document root
4. configure your virtual host with an alias `/jelix/` to `lib/jelix-www`
5. in gitiwiki/var/config, copy defaultconfig.ini.php.dist to defaultconfig.ini.php
   and profiles.ini.php.dist to profiles.ini.php
6. open a console and go into gitiwiki/install, and launch `php installer.php`

In next instructions, we assume that the domain name of your virtual host is
localhost.

Gitiwiki is almost ready. It needs now a Git repository.

Let's use the repository used for tests:

1. in the console, go into gitiwiki/var/repositories
2. unzip testrepos.zip. You should have a "default" directory.
3. open gitiwiki/var/config/defaultconfig.ini.php, and go at the end of file
4. in the gwrepo_default section, set the `path` property, by indicating the full
path to gitiwiki/var/repositories/default/

And that's all.

If you type http://localhost/index.php/wiki/default/, you should see a page with
a "hello world" ;-)


## Adding a repository

1. give a name to this repository (ex: mywiki)
2. create a new section in defaultconfig.ini.php, with this name and a prefix
"gwrepo_" (ex: gwrepo_mywiki)
3. indicate the path to this repository in a "path" parameter. This should be a
bare repository or the .git directory of your repository.
4. in the "branch" parameter, indicate the branch that gitiwiki should use 

    [gwrepo_mywiki]
    path=/home/myaccount/projects/mywiki/.git
    branch=master
    title= A title

You can also indicate a title for the list of wikis

## Content of a repository

Your repository can contain two type of files:

1. wiki pages, with the `.wiki` extension. The syntax is the dokuwiki syntax.
Later, gitiwiki will supports markdown and other syntaxes etc. 
2. any other files. Gitiwiki simply send them to the browser, with the right mime
type if it knows it.

### Links URL

URL of links in the wiki content are a bit different from the links in dokuwiki.
Path should not contain ":" but "/" like real path. You have four types of links:

1. Links relative to the current page: it does not start with a slash.
  ex: `[[foo/bar|my page]]`. If the current page is `http://localhost/index.php/wiki/mywiki/myarticle`,
  the link targets `http://localhost/index.php/wiki/mywiki/foo/bar`
2. Links relative to the wiki content: it should start with a slash.
  ex: `[[/foo/bar|my page]]`.  If the current page is `http://localhost/index.php/wiki/mywiki/dir/subdir/myarticle`,
  the link targets `http://localhost/index.php/wiki/mywiki/foo/bar`
3. Links relative to the domain name: it should start with two slashes.
  ex: `[[//foo/bar|my page]]`.  If the current page is `http://localhost/index.php/wiki/mywiki/dir/myarticle`,
  the link targets `http://localhost/foo/bar` (so it targets a page outside the wiki).
4. Absolute links: it should start with the `http://`. ex: `[[http://jelix.org|Jelix framework]]`

### Multiviews

Notice that you don't have to indicate the extension for wiki page, in URLs. For example,
if you target article.wiki, you can indicate simply `article`.
Gitiwiki will then try different extensions, indicated into a `.config.ini` file at
the root of your repository.

Here is an example of `.config.ini`

    multiviews=".wiki, .html, .txt"

With this configuration, when the url `myarticle` is given, Gitiwiki try to load first
the file myarticle.wiki. If it doesn't exist, it tries then to load myarticle.html,
and then myarticle.txt. If none of file are existing, a 404 error is returned.

### Home page and directory indexes

The home page should be stored into a file named "index" with one of the extension
indicated into the multiviews parameter. For example: index.wiki.

When the url correspond to a directory, Gitiwiki tries to load first a file with the same
name + a prefix indicated into the multiviews option, in the parent directory.

For exemple, if the url is `dir/subdir/`, it searches the file `dir/subdir.wiki` (and
then dir/subdir.html etc).

If this file doesn't exist, it searches an `index.wiki` (or `index.html` etc) file
inside the directory, so `dir/subdir/index.wiki`, then `dir/subdir/index.html` and so on.

### Redirections

Gitiwiki supports redirection. When you rename a file or move it into an other directory,
it is a good practice to do a HTTP redirection when the browser tries to load the old file.
It is better for search engines like Google for example.

When a file does not exist, Gitiwiki doesn't search into the git repository if it was
moved or renamed, because Git does not have a quick way to know it, and so it could be take
times and ressources to search (when there are several merge, it can be very difficult
and it could even fails, and if the file have been moved and modified at the same time,
it is impossible to find this rename).

So you have to indicate these changes into the `.config.ini` file, in the `redirection`
parameter.

Here an example (since there are several redirection parameters, you should use `[]`):

    redirection[] = "^manual2\.old/(.*)$ -> manual2/%s"
    redirection[] = "^manual2/unexistant -> manual2/article2"
    redirection[] = "^manual/moved-page-outside.txt -> //new-page.txt"
    redirection[] = "^something/elsewhere.txt -> http://jelix.org/new-page.txt"

A redirection information begins with a regular expression that matches the old url,
followed by an arrow "->", followed by the new url (which will be processed by sprintf
to do replacements). Not that new urls used the same rules as urls in links (see above).

The first redirection, redirects all URLS starting with "manual2.old/" to URLS starting
with "manual2/". It means: all files into the manual2.old directory were moved into the
manual2 directory.

The second redirection, redirects an URL of a simple file, to a new URL. It means:
manual2/unexistant has been renamed to manual2/article2

The third redirection means that the file moved-page-outside.txt of the wiki
have been moved to new-page.txt which is now outside the wiki, but it is still
in the same web site.

The  fourth redirection means that elsewhere.txt have been moved to an other
web site.

You can also indicate redirections in meta files. See below.


### Hidden files

All files that have names starting with a dot, will be ignored. All files inside a
directory that have a name starting with a dot, will be ignored two.

'ignored' means that a user cannot access to it, a simple "404" error will be returned.


### Meta files

Meta files are files containing extra-information about a specific file. A meta
file is stored in a `.meta` directory in the same directory of the target file.
its name is the same name of the target file + '.ini'.

For example, the meta file of mydir/article.wiki, is in mydir/.meta/article.wiki.ini.

You can have meta file for any file: images, pdf, text etc.

A meta file is an "ini" file, and could contain a title, a description, some keywords
etc..

For the moment, only a "redirection" parameter is supported. It is an alternative
way to the .config.ini file, to indicate a redirection. It means too that you
can have a meta file for an unexistant file.

For example, you moved a file mydir/article.wiki to otherdir/article.wiki, you
can have a mydir/.meta/article.wiki.ini file indicating:

     redirection="/otherdir/article.wiki"

## Design

For the moment, Gitiwiki doesn't really provide a design (but it's planned of course).
Since Gitiwiki is a Jelix application, to have your own design, simply
[follow instructions](http://jelix.org/articles/en/manual-1.3/themes)
to create a new theme, in the Jelix documentation.

Just copy the file `gitiwiki/modules/gitiwiki/templates/main.tpl` to 
`gitiwiki/var/themes/default/gitiwiki/main.tpl` and modify it. It must contains only
HTML content of the `<body>` element. If you want to add style sheets or javascript,
add these kind of tags at the begining of your templates:

    {meta_html css '/mystyles/my.css'}
    {meta_html js '/myscripts/fooscript.js'}

You should then have a `gitiwiki/www/mystyles/my.css` and a `gitiwiki/www/myscripts/fooscript.js` files.
