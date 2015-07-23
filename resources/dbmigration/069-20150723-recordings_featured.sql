ALTER TABLE  `recordings` ADD  `featureduntil` DATETIME NULL AFTER  `isfeatured`;
-- az isfeatured mezo innentol nem csak 0 es 1-et tartalmazhat hanem minel
-- nagyobb, annal fontosabb, igy isfeatured DESC szerint rendezve szepen
-- olcson megkapjuk a featuret
