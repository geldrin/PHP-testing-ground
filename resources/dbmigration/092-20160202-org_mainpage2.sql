ALTER TABLE `recordings`
ADD `combinedratingperweek` int(11) NOT NULL DEFAULT '0';
ALTER TABLE `recordings`
ADD `combinedratingpermonth` int(11) NOT NULL DEFAULT '0';

UPDATE recordings
SET combinedratingperweek = IFNULL((
  ratingthisweek *
  ( 100 * numberofratingsthisweek / numberofviewsthisweek ) *
  numberofviewsthisweek
), 0);

UPDATE recordings
SET combinedratingpermonth = IFNULL((
  ratingthismonth *
  ( 100 * numberofratingsthismonth / numberofviewsthismonth ) *
  numberofviewsthismonth
), 0);
