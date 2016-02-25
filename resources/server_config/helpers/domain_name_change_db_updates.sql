UPDATE
    recordings
SET
    mastersourceip = 'vsq-stream.pallasvideo.hu'
WHERE
    mastersourceip = 'vsq-stream.padabudapest.hu';

UPDATE
    recordings
SET
    contentmastersourceip = 'vsq-stream.pallasvideo.hu'
WHERE
    contentmastersourceip = 'vsq-stream.padabudapest.hu';

UPDATE
    attached_documents
SET
    sourceip = 'vsq-stream.pallasvideo.hu'
WHERE
    sourceip = 'vsq-stream.padabudapest.hu';
    
UPDATE
    users
SET
    avatarsourceip = 'vsq-stream.pallasvideo.hu'
WHERE
    avatarsourceip = 'vsq-stream.padabudapest.hu';

UPDATE
    view_statistics_live
SET
    streamserver = 'vsq-stream.pallasvideo.hu'
WHERE
    streamserver = 'vsq-stream.padabudapest.hu';
    
UPDATE
    view_statistics_ondemand
SET
    streamserver = 'vsq-stream.pallasvideo.hu'
WHERE
    streamserver = 'vsq-stream.padabudapest.hu';