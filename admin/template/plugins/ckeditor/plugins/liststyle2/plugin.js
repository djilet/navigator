(function() {
    CKEDITOR.plugins.liststyle2 = {
        requires: "dialog,contextmenu",
        init: function(a) {
            if (!a.blockless) {
                var b;
                b = new CKEDITOR.dialogCommand("numberedListStyle2", {
                    requiredContent: "ol",
                    allowedContent: "ol{list-style-type}[start]"
                });
                b = a.addCommand("numberedListStyle2", b);
                a.addFeature(b);
                CKEDITOR.dialog.add("numberedListStyle2", this.path + "dialogs/liststyle.js");
                a.addMenuGroup("list", 108);
                a.addMenuItems({
                    numberedlist2: {
                        label: a.lang.liststyle.numberedTitle2,
                        group: "list",
                        command: "numberedListStyle2",
                    },
                });
                a.contextMenu.addListener(function(a) {
                    if (!a || a.isReadOnly()) return null;
                    for (; a;) {
                        var b = a.getName();
                        if ("ol" == b) return {
                            numberedlist2: CKEDITOR.TRISTATE_OFF
                        };
                        a = a.getParent()
                    }
                    return null
                })
            }
        }
    };
    CKEDITOR.plugins.add("liststyle2", CKEDITOR.plugins.liststyle2)
})();