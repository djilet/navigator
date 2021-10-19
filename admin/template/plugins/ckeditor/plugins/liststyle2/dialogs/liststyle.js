/*
 Copyright (c) 2003-2015, CKSource - Frederico Knabben. All rights reserved.
 For licensing, see LICENSE.md or http://ckeditor.com/license
*/
(function() {
    function d(c, d) {
        var b;
        try {
            b = c.getSelection().getRanges()[0]
        } catch (f) {
            return null
        }
        b.shrink(CKEDITOR.SHRINK_TEXT);
        return c.elementPath(b.getCommonAncestor()).contains(d, 1)
    }

    function e(c, e) {
        var b = c.lang.liststyle;
        if ("numberedListStyle2" == e) {
            var p = [
                [b.notset, ""],
                [b.bigPin, "big-pin"],
                [b.middlePin, "middle-pin"],
                [b.smallPin, "small-pin"],
            ];
            return {
                title: b.numberedTitle2,
                minWidth: 250,
                minHeight: 50,
                contents: [{
                    id: "info",
                    accessKey: "I",
                    elements: [{
                        type: "hbox",
                        widths: ["25%", "75%"],
                        children: [{
                            label: b.start,
                            type: "text",
                            id: "start",
                            validate: CKEDITOR.dialog.validate.integer(b.validateStartNumber),
                            setup: function(a) {
                                this.setValue(a.getFirst(f).getAttribute("value") ||
                                    a.getAttribute("start") || 1);
                            },
                            commit: function(a) {
                                var b = a.getFirst(f),
                                    c = b.getAttribute("value") || a.getAttribute("start") || 1;
                                a.getFirst(f).removeAttribute("value");
                                var d = parseInt(this.getValue(), 10);
                                isNaN(d) ? a.removeAttribute("start") : a.setAttribute("start", d);
                                isNaN(d) ? a.removeAttribute("style") : a.setAttribute("style", "counter-reset: b " + (a.getAttribute("start") - 1) + ";");
                                a = b;
                                b = c;
                                for (d = isNaN(d) ? 1 : d;
                                     (a = a.getNext(f)) && b++;) a.getAttribute("value") == b && a.setAttribute("value", d + b - c)

                            }
                        }, {
                            type: "select",
                            label: b.pin,
                            id: "pin",
                            style: "width: 100%;",
                            items: p,
                            setup: function(a) {
                                this.setValue(a.getAttribute("class") || "big-pin")
                            },
                            commit: function(a) {
                                var b = this.getValue();
                                b ? a.setAttribute("class", b) : a.removeAttribute("class")
                            }
                        }]
                    }]
                }],
                onShow: function() {
                    var a = this.getParentEditor();
                    (a = d(a, "ol")) && this.setupContent(a)
                },
                onOk: function() {
                    var a = this.getParentEditor();
                    (a = d(a, "ol")) && this.commitContent(a)
                }
            }
        }
    }
    var f = function(c) {
            return c.type == CKEDITOR.NODE_ELEMENT && c.is("li")
        };
    CKEDITOR.dialog.add("numberedListStyle2", function(c) {
        return e(c, "numberedListStyle2")
    })
})();