PATH=/Applications/Locomotive2/Bundles/standardRailsSept2006.locobundle/powerpc/bin/:$PATH xgettext --debug --keyword=_e:1,2t --keyword=__:1,2t ../*.php -o messages.pot
PATH=/Applications/Locomotive2/Bundles/standardRailsSept2006.locobundle/powerpc/bin/:$PATH msgmerge zensor.pot messages.pot -o zensor.pot
rm messages.pot
# For initializing new translations
# PATH=/Applications/Locomotive2/Bundles/standardRailsSept2006.locobundle/powerpc/bin/:$PATH msginit -i zensor.pot -l de_DE -o zensor-de_DE.po
# PATH=/Applications/Locomotive2/Bundles/standardRailsSept2006.locobundle/powerpc/bin/:$PATH msginit -i zensor.pot -l en_US -o zensor-en_US.po
PATH=/Applications/Locomotive2/Bundles/standardRailsSept2006.locobundle/powerpc/bin/:$PATH msgmerge zensor-de_DE.po zensor.pot -o zensor-de_DE.po
#PATH=/Applications/Locomotive2/Bundles/standardRailsSept2006.locobundle/powerpc/bin/:$PATH msgmerge zensor-en_US.po zensor.pot -o zensor-en_US.po
