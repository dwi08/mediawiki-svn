(function() {

// h1

// h2 ...

var headers = [];

function createSection0() {
    var nodeList = document.getElementsByTagName('h1'),
        h1 = nodeList[0],
        title = h1.textContent,
        bodyContent = document.getElementById('bodyContent'),
        content = bodyContent.getElementsByTagName('div')[0],
        content_0 = document.createElement('div');
    content_0.className = 'content_block';
    content_0.id = 'content_0';
    
    
    var hide = document.createElement('button'),
        show = document.createElement('button'),
        anchor = document.createElement('div');
    hide.textContent = 'Hide';
    hide.className = 'section_heading hide';
    show.textContent = 'Show';
    show.className = 'section_heading show';
    
    
    h1.insertBefore(hide, h1.firstChild);
    h1.insertBefore(show, h1.firstChild);
    
    // find next section!
    var node = content.firstChild,
        allHeaders = {H1: 1, H2: 2, H3: 3, H4: 4, H5: 5, H6: 6};
    while (node !== null && !(node.tagName in allHeaders)) {
        var next = node.nextSibling;
        node.parentNode.removeChild(node);
        content_0.appendChild(node);
        node = next;
    }
    content.insertBefore(content_0, content.firstChild);
    content.insertBefore(anchor, content.firstChild);
    h1.id = "section_0";
    h1.class = "section_heading";
    h1.onclick = function() {
        wm_toggle_section(0);
    };

    headers.push({
        node: h1,
        section: 0,
        id: '',
        text: title
    });
}

function processHeader(node) {
    if (node.className != 'section_heading') {
        return; // skip
    }
    var spans = node.getElementsByTagName('span'),
        span = spans[0],
        matches = node.id.match(/^section_(\d+)$/),
        section = parseInt(matches[1]);
    headers.push({
        node: node,
        section: section,
        id: span.id,
        text: span.textContent
    });
}

function processHeadersByTag(tag) {
    var nodeList = document.getElementsByTagName(tag);
    for (var i = 0; i < nodeList.length; i++) {
        processHeader(nodeList[i]);
    }
}

function createMagicToc() {
    headers.sort(function(a, b) {
        if (a.section == b.section) {
            return 0;
        } else if (a.section > b.section) {
            return 1;
        } else {
            return -1;
        }
    });
    
    var contentWrapper = document.getElementById('content_wrapper'),
        magicToc = document.createElement('div'),
        ul = document.createElement('ul');
    magicToc.id = 'magic-toc';
    contentWrapper.parentNode.insertBefore(magicToc, contentWrapper);
    magicToc.appendChild(ul);

    for (var i = 0; i < headers.length; i++) {
        (function() {
            var header = headers[i],
                li = document.createElement('li'),
                a = document.createElement('a');
            a.textContent = header.text;
            a.href = '#' + header.id;
            a.onclick = function() {
                wm_toggle_section(header.section);
            };
            li.appendChild(a);
            ul.appendChild(li);
        })();
    }
}

createSection0();
processHeadersByTag('h1');
processHeadersByTag('h2');
processHeadersByTag('h3');
processHeadersByTag('h4');
processHeadersByTag('h5');
processHeadersByTag('h6');

createMagicToc();

})();
