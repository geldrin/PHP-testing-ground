UPDATE recordings
SET accesstype = 'departmentsorgroups'
WHERE accesstype IN('departments', 'groups');

UPDATE livefeeds
SET accesstype = 'departmentsorgroups'
WHERE accesstype IN('departments', 'groups');

UPDATE channels
SET accesstype = 'departmentsorgroups'
WHERE accesstype IN('departments', 'groups');
