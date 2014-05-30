html2text
=========

html2text is a very simple script that uses PHP's DOM methods to load from HTML, and then iterates over the resulting DOM to correctly output plain text. For example:

```html
<html>
<title>Ignored Title</title>
<body>
  <h1>Hello, World!</h1>

  <p>This is some e-mail content.
  Even though it has whitespace and newlines, the e-mail converter
  will handle it correctly.

  <p>Even mismatched tags.</p>

  <div>A div</div>
  <div>Another div</div>
  <div>A div<div>within a div</div></div>

  <a href="http://foo.com">A link</a>

</body>
</html>
```

Will be converted into:

```text
Hello, World!

This is some e-mail content. Even though it has whitespace and newlines, the e-mail converter will handle it correctly.

Even mismatched tags.
A div
Another div
A div
within a div
[A link](http://foo.com)
```

See the [original blog post](http://journals.jevon.org/users/jevon-phd/entry/19818) or the related [StackOverflow answer](http://stackoverflow.com/a/2564472/39531).

## Installing

You can use [Composer](http://getcomposer.org/) to add the [package](https://packagist.org/packages/soundasleep/jsonrpcclient) to your project:

```json
{
  "require": {
    "soundasleep/html2text": "~0.1.1"
  }
}
```

And then use it quite simply:

```php
$text = convert_html_to_text($html);
```

## Tests

Some very basic tests are provided in the `tests/` directory. Run them with `php -f run.php`.
