# Advanced contact form plugin for WonderCMS
[![Issue Progress](https://img.shields.io/badge/%E2%9C%93-Issue%20Progress-gray?labelColor=brightgreen&style=flat)](https://crypt.stanisic.nl/kanban/#/2/kanban/view/p6mqokEiUAhkSAJsJVWJyDn04dYvNAkWBLtt4PRF7ZU/)
[![WonderCMS 3.x.x](https://img.shields.io/badge/WonderCMS-3.x.x-%231ab?style=flat)](https://github.com/robiso/wondercms)

## Description
Plugin for adding a contact form to a WonderCMS website.
Inspired by [robiso/contact-form](https://github.com/robiso/contact-form), but with a config file.

## Preview
![Plugin preview](/preview.jpg)


# Instructions
This plugins requires some additional steps to work

## 1. Install plugin
1. Login to your WonderCMS website.
2. Click "Settings" and click "Plugins".
3. Paste the url of this repo into the url field at the bottom.
4. Click add

## 2. Config
The plugin folder contains a file called `config.json.example`. Copy it to `config.json` and edit the file. It is self-documented.

#### On configuring the form
In the config file you can edit the form to your liking. The configuration is as follows:
```
"fields": {
    "name": "text",
    "email": "email",
    "category": [
        "Billing",
        "Technical support"
    ],
    "message": "textarea"
}
```
It's a key-value configuration where the key is the name of the field, and the value is the type of field. So for a input element with the type of `number` you would add `"age": "number",` to the `fields` array.
When the value is a array, the input element `select` will be used.
_Input types checkbox and radio can't be used yet._

## 3. Add to page
Add the shortcode for this plugin to the contents of your page. This defaults to `[contact_form]`. When you sign out the shortcode will be replaced with the form.
