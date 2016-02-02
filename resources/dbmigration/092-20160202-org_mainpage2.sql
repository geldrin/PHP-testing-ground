ALTER TABLE `recordings`
ADD `combinedratingperweek` int(11) NOT NULL DEFAULT '0';
ALTER TABLE `recordings`
ADD `combinedratingpermonth` int(11) NOT NULL DEFAULT '0';

UPDATE recordings
SET combinedratingperweek = (
  ratingthisweek *
  ( 100 * numberofratingsthisweek / numberofviewsthisweek ) *
  numberofviewsthisweek
);

UPDATE recordings
SET combinedratingpermonth = (
  ratingthismonth *
  ( 100 * numberofratingsthismonth / numberofviewsthismonth ) *
  numberofviewsthismonth
);
