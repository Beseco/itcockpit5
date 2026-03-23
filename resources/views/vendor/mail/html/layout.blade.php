<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>{{ config('app.name') }}</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta name="color-scheme" content="light">
<meta name="supported-color-schemes" content="light">
<style>
@media only screen and (max-width: 600px) {
.inner-body { width: 100% !important; }
.footer { width: 100% !important; }
}
@media only screen and (max-width: 500px) {
.button { width: 100% !important; }
}

* { box-sizing: border-box; }
body, html { margin: 0; padding: 0; }

body {
    background-color: #f3f4f6;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
    font-size: 16px;
    line-height: 1.6;
    color: #374151;
    -webkit-text-size-adjust: none;
}

.wrapper {
    background-color: #f3f4f6;
    margin: 0;
    padding: 0;
    width: 100%;
}

.content {
    margin: 0;
    padding: 0;
    width: 100%;
}

.header {
    padding: 28px 0;
    text-align: center;
    background-color: #4338ca;
}

.header a {
    color: #ffffff;
    font-size: 20px;
    font-weight: 700;
    text-decoration: none;
    letter-spacing: 0.5px;
}

.body {
    background-color: #f3f4f6;
    border: hidden !important;
    margin: 0;
    padding: 0;
    width: 100%;
}

.inner-body {
    background-color: #ffffff;
    border-color: #e5e7eb;
    border-radius: 8px;
    border-width: 1px;
    border-style: solid;
    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
    margin: 0 auto;
    padding: 0;
    width: 570px;
}

.content-cell {
    max-width: 100vw;
    padding: 36px 48px;
}

.footer {
    margin: 0 auto;
    padding: 0;
    text-align: center;
    width: 570px;
}

.footer td {
    color: #9ca3af;
    font-size: 13px;
    text-align: center;
    padding: 20px 0;
}

p { color: #374151; font-size: 16px; line-height: 1.6; margin: 0 0 16px; }
a { color: #4338ca; }
a:hover { color: #3730a3; }

.button { margin: 0 auto; }
.button td { border-radius: 6px; }

.button-primary td,
.button-primary a {
    background-color: #4338ca !important;
    border: 10px solid #4338ca !important;
    border-radius: 6px !important;
    color: #ffffff !important;
    display: inline-block !important;
    font-size: 14px !important;
    font-weight: 600 !important;
    letter-spacing: 0.5px !important;
    text-decoration: none !important;
    text-transform: uppercase !important;
    min-width: 200px !important;
    text-align: center !important;
}

.panel { border-left: 4px solid #4338ca; margin: 0 0 20px; padding: 16px 20px; background-color: #eef2ff; border-radius: 0 6px 6px 0; }
.panel-content p { color: #374151; margin: 0; }

h1, h2, h3 { color: #111827; margin: 0 0 16px; }
h1 { font-size: 20px; font-weight: 700; }
h2 { font-size: 18px; font-weight: 600; }

table { width: 100%; }

.subcopy { border-top: 1px solid #e5e7eb; margin-top: 24px; padding-top: 16px; }
.subcopy p, .subcopy td { color: #9ca3af; font-size: 13px; line-height: 1.5; }
</style>
{!! $head ?? '' !!}
</head>
<body>

<table class="wrapper" width="100%" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td align="center">
<table class="content" width="100%" cellpadding="0" cellspacing="0" role="presentation">
{!! $header ?? '' !!}

<!-- Email Body -->
<tr>
<td class="body" width="100%" cellpadding="0" cellspacing="0" style="border: hidden !important;">
<table class="inner-body" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation">
<!-- Body content -->
<tr>
<td class="content-cell">
{!! Illuminate\Mail\Markdown::parse($slot) !!}

{!! $subcopy ?? '' !!}
</td>
</tr>
</table>
</td>
</tr>

{!! $footer ?? '' !!}
</table>
</td>
</tr>
</table>
</body>
</html>
