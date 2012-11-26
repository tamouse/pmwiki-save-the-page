# Makefile for managing the SaveThePage PmWiki cookbook recipe

ZIP=/usr/bin/zip
ZIPOPTIONS=-ru

all: clean savethepage.zip

clean:
	find . -name '*~' -delete

savethepage.zip:
	$(ZIP) $(ZIPOPTIONS) $@ savethepage.php savethepage