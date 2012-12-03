# Makefile for managing the SaveThePage PmWiki cookbook recipe

OBJECTS=savethepage.php savethepage/LICENSE.txt savethepage/simple_html_dom.php savethepage/bookmarklet.js savethepage/README.txt savethepage/bundlepages.php savethepage/wikilib.d/Site.SaveThePage savethepage/SaveThePage.php


ZIP=/usr/bin/zip
ZIPOPTIONS=-r

all: clean savethepage.zip

clean:
	find . -name '*~' -delete

savethepage.zip: $(OBJECTS) Makefile
	[ -f savethepage.zip ] && rm savethepage.zip || true
	$(ZIP) $(ZIPOPTIONS) $@ savethepage.php savethepage
