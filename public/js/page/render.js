function addWidgHalToTinyMCE(tinymce)
{
    tinymce.PluginManager.add("widghal", function (t, e) {

        var instance;

        function WidghalPlugin(t, e) {
            instance = this;

            this.__command__(instance, t, e);
            this.__buttons__(instance, t, e);
        }

        WidghalPlugin.prototype = {
            canPress: true,
            isOver: false,
            isDown: false,
            isUp: false,
            style: 'background-color: #FFFFFF; border: 1px solid #DDDDDD; border-radius: 4px; margin: 20px 0; padding: 0; position: relative; cursor: pointer;',
            style2: 'background-color: #FFFFFF; border: 1px dashed grey; border-radius: 4px; margin: 20px 0; padding: 0; position: relative; cursor: pointer;',
            selector: 'div[data-mce-widget]' + (navigator.userAgent.toLowerCase().indexOf('chrome') > -1 ? '[data-mce-selected=1]' : '[data-mce-selected=0]'),
            pattern: [
                {"news": {"title": {"input": "text", "value": ""}, "limit": {"input": "text", "value": 5}}},
                {
                    "feed": {
                        "title": {"input": "text", "value": ""},
                        "href": {"input": "text", "value": ""},
                        "limit": {"input": "text", "value": 5}
                    }
                },
                {
                    "count": {
                        "title": {"input": "text", "value": ""},
                        "format": {"input": "select", "value": {0: "file", 1: "notice", 2: "annex"}}
                    }
                },
                {"last": {"title": {"input": "text", "value": ""}, "limit": {"input": "text", "value": 10}}},
                {"lastpub": {"title": {"input": "text", "value": ""}, "limit": {"input": "text", "value": 10}}},
                {
                    "twitter": {
                        "title": {"input": "text", "value": ""},
                        "href": {"input": "text", "value": ""},
                        "format": {"input": "select", "value": {0: "timeline", 1: "tweet", 2: "follow"}}
                    }
                },
                {
                    "cartohal": {
                        "title": {"input": "text", "value": ""},
                        "display": {"input": "select", "value": {0: "map", 1: "table", 2: "mapAndTable"}},
                        "searchCriteria": {"input": "select", "value": {
                            0:"structCountry_s",
                            1:"rteamStructCountry_s",
                            2:"deptStructCountry_s",
                            3:"labStructCountry_s",
                            4:"rgrpLabStructCountry_s",
                            5:"instStructCountry_s",
                            6:"rgrpInstStructCountry_s",
                            7:"country_s"}}
                    }
                },
                {"link": {"title": {"input": "text", "value": ""}, "links": {"input": "multitext"}}},
                {
                    "stats": {
                        "title": {"input": "text", "value": ""},
                        "format": {"input": "select", "value": {0: "domain", 1: "typdoc", 2: "evol"}}
                    }
                },
                {"search": {"title": {"input": "text", "value": ""}}},
                {"searchAdv": {"title": {"input": "text", "value": ""}}},
                {"sherpa": {"title": {"input": "text", "value": ""}}},
                {"cloud": {"title": {"input": "text", "value": ""}}},
            ],
            add: function (e) {
                var self = this, f = true, o, callBack, that;

                var callBack = function () {
                    $(that).find('input').removeClass('error');
                };


                $(e).parent().find('div:first input').each(function (i) {
                    if (!$(this).val()) {
                        f = false, that = $(this).parent();
                        $(this).addClass('error');
                        setTimeout(callBack, 1000);
                    }
                });

                var name = $(e).parent().find('div:first input[data-field="name"]').val();
                var url = $(e).parent().find('div:first input[data-field="url"]').val();

                if (f) {
                    if (!$(e).closest('.form-group').find('ul').hasClass('ui-sortable')) {
                        $(e).closest('.form-group').find('ul').sortable();
                    }

                    $(e).closest('.form-group').find('ul').append('<li></li>');
                    $(e).closest('.form-group').find('ul li:last').append('<span>' + $(e).parent().find('div:first input[data-field="name"]').val() + '&nbsp;<span style="cursor: pointer; font-size: 10px; margin: 0 0 0 5px; padding: 0;" onmouseup="$(this).closest(\'li\').remove(); return false;">X</span></span>');
                    $(e).closest('.form-group').find('ul li:last').append('<input type="hidden" name="' + url + '" value="' + name + '" data-group="links"/>');
                    $(e).parent().find('div:first input').val("");
                }

                return false;
            },
            renderHTML: function (instance, t, e, i, j) {
                var xhtml;
                return xhtml = '<table class="table widghal widghal_settings"><tr><td class="list"><table class="table">',
                    tinymce.each(WidghalPlugin.prototype.pattern, function (a) {
                        xhtml += "<tr>",
                            tinymce.each(a, function (a, b) {
                                xhtml += '<td data-type="' + b + '" class="' + (b == i ? 'active_item' : '') + '">'
                                    + '<div class="arrow_box" data-type="' + b + '">'
                                    + tinymce.util.I18n.translate('widghal_' + b)
                                    + '</div>'
                                    + '</td>'
                            }),
                            xhtml += "</tr>"
                    }),
                    xhtml += '</table></td><td><form onsubmit="" name="form_widghal">',
                    tinymce.each(WidghalPlugin.prototype.pattern, function (a) {
                        tinymce.each(a, function (a, b) {
                            x = b;
                            xhtml += '<div class="' + b + ' widghal_item" data-set="true" data-cat="' + b + '" ' + (i == b ? 'data-display="true" ' : '')
                                + (b == i ? '' : ' style="display: none;"')
                                + '>',
                                tinymce.each(a, function (a, b) {
                                    xhtml += '<div class="form-group">'
                                        + '<label>'
                                        + tinymce.util.I18n.translate('widghal_' + x + '_' + b)
                                        + '</label>'
                                        + instance.renderINPUT(t, e, a, b, x, j)
                                        + '</div>'
                                }),
                                xhtml += '</div>'
                        })
                    }),
                    xhtml += '<div class="widghal_button"><button class="btn btn-primary" id="add">' + (j == undefined ? tinymce.util.I18n.translate('widghal_add') : tinymce.util.I18n.translate('widghal_modify')) + '</button></div></form></td></tr></table>'
            },
            renderINPUT: function (t, e, a, b, x, j) {
                var d = 1;
                var name = b;
                var input = '<%type_b% class="form-control input-sm" name="%name%" %value%></%type_e%>';
                var field = b;

                return u = '',

                    tinymce.each(a, function (a, b) {

                        switch (b) {
                            case 'input'  :
                                if (a == 'text') {
                                    d = 1;
                                    input = input.replace(new RegExp("(%name%)", "g"), name);
                                    input = input.replace(new RegExp("(%type_b%)", "g"), 'input type="text"');
                                    input = input.replace(new RegExp("(%type_e%)", "g"), 'input');
                                }
                                else if (a == 'multitext') {
                                    d = 1, input2 = input;

                                    input = input.replace(new RegExp("(%name%)", "g"), name + '[\'link\'][]"' + ' data-field="url" id="data-field-url" title="' + tinymce.util.I18n.translate('widghal_' + x + '_' + field + '_url') + '" alt="' + tinymce.util.I18n.translate('widghal_' + x + '_' + field + '_url') + '" placeholder="' + tinymce.util.I18n.translate('widghal_' + x + '_' + field + '_url'));
                                    input = input.replace(new RegExp("(%type_b%)", "g"), 'input type="text"');
                                    input = input.replace(new RegExp("(%type_e%>)", "g"), 'input>');

                                    input2 = input2.replace(new RegExp("(%name%)", "g"), name + '[\'name\'][]"' + ' data-field="name" id="data-field-name" title="' + tinymce.util.I18n.translate('widghal_' + x + '_' + field + '_urlName') + '" alt="' + tinymce.util.I18n.translate('widghal_' + x + '_' + field + '_urlName') + '" placeholder="' + tinymce.util.I18n.translate('widghal_' + x + '_' + field + '_urlName'));
                                    input2 = input2.replace(new RegExp("(%type_b%)", "g"), 'input type="text"');
                                    input2 = input2.replace(new RegExp("(%type_e%>)", "g"), 'input>');

                                    input = '<div class="widghal_links"><div>' + input + input2 + '</div><button type="button" onclick="tinymce.activeEditor.plugins.widghal.add(this);" class="btn btn-default btn-plus">+</button></div><ul>';

                                    if (j != undefined && x == 'link' && j['links'] != undefined) {
                                        for (var url in j['links']) {
                                            input = input + '<li onmouseover=\'if (!$(this).parent().hasClass("ui-sortable")) { $(this).parent().sortable(); }\'>';
                                            input = input + '<span>' + j['links'][url] + '&nbsp;<span style="cursor: pointer; font-size: 10px; margin: 0 0 0 5px; padding: 0;" onmouseup="$(this).closest(\'li\').remove(); return false;">X</span></span>';
                                            input = input + '<input type="hidden" name="' + url + '" value="' + j['links'][url] + '" data-group="links"/>';
                                            input = input + '</li>';
                                        }
                                    }

                                    input = input + '</ul>';
                                }
                                else {
                                    d = 2;
                                    input = input.replace(new RegExp("(%name%)", "g"), name);
                                    input = input.replace(new RegExp("(%type_b%)", "g"), 'select');
                                    input = input.replace(new RegExp("(%value%></%type_e%)", "g"), '>%value%</select');
                                }
                                u += ''
                                break;
                            case 'value'  :
                                if (d == 1) {
                                    input = input.replace(new RegExp("(%value%)", "g"), 'value="' + ((j != undefined && j['type'] == x && j[field] != undefined) ? j[field] : a) + '"');
                                }
                                else {
                                    ;
                                    tinymce.each(a, function (m, n) {
                                        input = input.replace(new RegExp("(%value%)", "g"), '<option value="' + m + ((m == ((j != undefined && j['type'] == x && j[field] != undefined) ? j[field] : a)) ? '" selected="selected"' : '"') + '>' + tinymce.util.I18n.translate('widghal_' + x + '_' + field + '_' + m) + '</option>%value%');
                                    })
                                    input = input.replace(new RegExp("(%value%)", "g"), '');
                                }
                                u += ''
                                break;
                            default       :
                                u += ''
                        }
                    }),

                    u += input
            },
            converseDOM: function (nodes, name) {
                var i = nodes.length, node, placeHolder;

                while (i--) {
                    node = nodes[i];

                    if (node.name != 'widget') {
                        continue;
                    }

                    var j = tinymce.util.JSON.parse(node.firstChild.value);

                    placeHolder = new tinymce.html.Node('div', 1);
                    placeHolder.attr({
                        'style': instance.style,
                        'data-mce-widget': node.firstChild.value,
                        'data-mce-selected': '0'
                    });

                    h3 = new tinymce.html.Node('h3', 1);
                    h3.attr({
                        'style': 'background-color: #F7F7F7; border-bottom: 1px solid #EBEBEB; border-radius: 5px 5px 0 0; color: #D56022; font-size: 14px; font-weight: bold; line-height: 18px; margin: 0; padding: 8px 14px; text-transform: uppercase;',
                        'data-mce-widget-in': 'true'
                    });

                    h3Text = new tinymce.html.Node('text', 3)
                    h3Text.value = j['title'] ? j['title'] : j['type'];

                    h3.append(h3Text);

                    div = new tinymce.html.Node('div', 1);
                    div.attr({
                        'style': 'margin: 0;padding: 8px 14px',
                        'data-mce-widget-in': 'true'
                    });

                    divh3 = new tinymce.html.Node('h3', 1);
                    divh3.attr({
                        'style': 'text-align: center;',
                        'data-mce-widget-in': 'true'
                    });

                    divh3span = new tinymce.html.Node('span', 1);
                    divh3span.attr({
                        'style': 'background-color: #999999; border-radius: 0.25em; color: #FFFFFF; display: inline; font-size: 75%; font-weight: bold; line-height: 1; padding: 0.2em 0.6em 0.3em; text-align: center; vertical-align: baseline; white-space: nowrap;',
                        'data-mce-widget-in': 'true'
                    });

                    divh3spanText = new tinymce.html.Node('text', 3)
                    divh3spanText.value = "Contenu indisponible en édition";

                    divh3span.append(divh3spanText);

                    divh3.append(divh3span);
                    div.append(divh3);

                    placeHolder.append(h3);
                    placeHolder.append(div);

                    node.replace(placeHolder);
                }
            },
            reverseDOM: function (nodes, name) {
                var i = nodes.length, node, widget;

                while (i--) {
                    node = nodes[i];

                    widget = new tinymce.html.Node('widget', 1);

                    widgetText = new tinymce.html.Node('text', 3);
                    widgetText.value = node.attr('data-mce-widget');

                    widget.append(widgetText);

                    node.replace(widget);
                }
            },
            __command__: function (instance, t, e) {
                t.addCommand('mycommand', function (e) {
                    var j;

                    $(t.getBody()).find('[data-mce-widget][data-mce-selected=1]').each(function (i) {
                        j = tinymce.util.JSON.parse($(this).attr('data-mce-widget'));
                    });

                    var type = 'news';

                    if (j != undefined) {
                        type = j['type'];
                    }

                    $(".widghal_settings").html(instance.renderHTML(instance, t, e, type, j != undefined ? j : undefined));
                });
            },
            __buttons__: function (instance, t, e) {
                t.addButton("widghal", {
                    type: "panelbutton",
                    panel: {
                        role: "application",
                        autohide: !0,
                        html: instance.renderHTML(instance, 'news'),
                        onclick: function (e) {
                            if (e.target.id == 'add') {
                                var j = new Object({"type": $(".widghal_settings").find('[data-display]').attr('data-cat')});

                                $(".widghal_settings").find('[data-display]').find('input,select').each(function (i) {
                                    if (!$(this).attr('data-field')) {
                                        if (!$(this).attr('data-group')) {
                                            j[$(this).attr('name')] = $(this).val();
                                        } else {
                                            if (j[$(this).attr('data-group')] == undefined) {
                                                j[$(this).attr('data-group')] = new Object();
                                            }
                                            j[$(this).attr('data-group')][$(this).attr('name')] = $(this).val();
                                        }
                                    }
                                });

                                if (t.selection.getNode().getAttribute('data-mce-widget') || t.selection.getNode().getAttribute('data-mce-widget-in')) {
                                    var node = t.selection.getNode();
                                    while (node.parentNode != undefined && node.getAttribute('data-mce-widget') == undefined) node = node.parentNode;
                                    if (node == undefined) return false;
                                    node.remove();
                                }

                                t.execCommand("mceInsertContent", false, '<widget>' + tinymce.util.JSON.serialize(j) + '</widget><p>&nbsp;</p>');
                            } else if (typeof $(e.target).attr('data-type') != 'undefined') {
                                $(t.dom.getParent(e.target)).closest('table.widghal').find('.active_item').removeClass('active_item');
                                $(t.dom.getParent(e.target)).closest('tr').find('td').addClass('active_item');
                                $(t.dom.getParent(e.target)).closest('table.widghal').find("[data-set]").removeAttr('data-display').hide();
                                $(t.dom.getParent(e.target)).closest('table.widghal').find('.' + $(t.dom.getParent(e.target)).attr('data-type')).attr('data-display', 'true').show();
                                e.stopPropagation();
                            } else e.stopPropagation();

                            return false;
                        },
                    },
                    tooltip: "Widgets",
                    stateSelector: [instance.selector],
                    cmd: 'mycommand'
                })
            }
        }


        /**
         * FILTERS
         */

        t.on('preInit', function () {
            t.parser.addNodeFilter('widget', instance.converseDOM);
            t.serializer.addAttributeFilter('data-mce-widget', instance.reverseDOM);
        });


        /**
         * EVENTS
         */

        t.on('mouseover', function (e) {
            if (e.target.getAttribute('data-mce-widget') || e.target.getAttribute('data-mce-widget-in')) {
                while (e.target.parentNode != undefined && e.target.getAttribute('data-mce-widget') == undefined) e.target = e.target.parentNode;
                e.target.setAttribute('style', instance.style2);
                instance.isOver = true;
            }
        });

        t.on('mouseout', function (e) {
            if (e.target.getAttribute('data-mce-widget') || e.target.getAttribute('data-mce-widget-in')) {
                while (e.target.parentNode != undefined && e.target.getAttribute('data-mce-widget') == undefined) e.target = e.target.parentNode;
                if (e.target.getAttribute('data-mce-selected') == '0') {
                    e.target.setAttribute('style', instance.style);
                }
                instance.isOver = false;
            }
        });

        t.on('mousedown', function (e) {
            instance.isDown = Boolean(e.target.getAttribute('data-mce-widget') || e.target.getAttribute('data-mce-widget-in'));
        });

        t.on('mouseup', function (e) {
            instance.isUp = Boolean(e.target.getAttribute('data-mce-widget') || e.target.getAttribute('data-mce-widget-in'));
        });

        t.on('click', function (e) {
            if (e.target.getAttribute('data-mce-widget') || e.target.getAttribute('data-mce-widget-in')) {
                while (e.target.parentNode != undefined && e.target.getAttribute('data-mce-widget') == undefined) e.target = e.target.parentNode;

                if (e.target.getAttribute('data-mce-selected') == '1') {
                    e.target.setAttribute('style', instance.style);
                    $(e.target).attr('data-mce-selected', '0');
                    return true;
                }
            }

            $(t.getBody()).find('[data-mce-widget]').each(function (i) {
                $(this).attr('style', instance.style);
                $(this).attr('data-mce-selected', '0');
            });

            if (e.target.getAttribute('data-mce-widget') || e.target.getAttribute('data-mce-widget-in')) {
                while (e.target.parentNode != undefined && e.target.getAttribute('data-mce-widget') == undefined) e.target = e.target.parentNode;

                t.selection.setCursorLocation(e.target);

                e.target.setAttribute('style', instance.style2);
                e.target.setAttribute('data-mce-selected', '1');
            }
        });

        t.on("keydown", function (e) {
            if (e.keyCode == 37 || e.keyCode == 38 || e.keyCode == 39 || e.keyCode == 40) {
                return true;
            }

            if (!(!instance.isDown && !instance.isUp)) {
                if (Boolean(t.selection.getContent())) {
                    return false;
                }
            }

            if (t.selection.getNode().getAttribute('data-mce-widget') || t.selection.getNode().getAttribute('data-mce-widget-in')) {
                var node = t.selection.getNode();
                while (node.parentNode != undefined && node.getAttribute('data-mce-widget') == undefined) node = node.parentNode;
                if (node == undefined) return false;
                if (e.keyCode == 13) {
                    var child = node, i = 0;
                    while ((child = child.previousSibling) != null) i++;
                    if (i == 0) {
                        $('<span></span>').insertBefore(node.parentNode.children[i]);
                        t.selection.setCursorLocation(node.parentNode.children[i]);
                    } else if (i > 1 && node.parentNode.children[i - 2].getAttribute('data-mce-widget') != undefined) {
                        $('<span></span>').insertAfter(node.parentNode.children[i - 2]);
                        t.selection.setCursorLocation(node.parentNode.children[i - 1]);
                    } else t.selection.setCursorLocation(node.parentNode.children[i - 1]);
                    return true;
                } else if (e.keyCode == 46) {
                    node.remove();
                }
                return false;
            } else {
                if (t.selection.getContent()) {
                    var n = new tinymce.html.Serializer().serialize(new tinymce.html.DomParser().parse(t.selection.getContent()));
                    return (n[0] == '<' && n[n.length - 1] == '>');
                }
            }
        });

        return new WidghalPlugin(t, e);
    });
}