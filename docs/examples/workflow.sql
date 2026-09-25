-- Example workflow. Adds missing rules; preserves existing rules unchanged.
-- Statuses must already exist. No group roles are assigned.
START TRANSACTION;

INSERT INTO tl_issue_transition (tstamp, from_status_id, to_status_id, role_key, require_public_comment, published)
SELECT UNIX_TIMESTAMP(), source.id, target.id, 'agent', 0, 1
FROM tl_issue_status source CROSS JOIN tl_issue_status target
WHERE source.status_key='new' AND target.status_key='triage'
AND NOT EXISTS (SELECT 1 FROM tl_issue_transition existing WHERE existing.from_status_id=source.id AND existing.to_status_id=target.id AND existing.role_key='agent');

INSERT INTO tl_issue_transition (tstamp, from_status_id, to_status_id, role_key, require_public_comment, published)
SELECT UNIX_TIMESTAMP(), source.id, target.id, 'agent', 0, 1
FROM tl_issue_status source CROSS JOIN tl_issue_status target
WHERE source.status_key='new' AND target.status_key='in_progress'
AND NOT EXISTS (SELECT 1 FROM tl_issue_transition existing WHERE existing.from_status_id=source.id AND existing.to_status_id=target.id AND existing.role_key='agent');

INSERT INTO tl_issue_transition (tstamp, from_status_id, to_status_id, role_key, require_public_comment, published)
SELECT UNIX_TIMESTAMP(), source.id, target.id, 'agent', 0, 1
FROM tl_issue_status source CROSS JOIN tl_issue_status target
WHERE source.status_key='triage' AND target.status_key='in_progress'
AND NOT EXISTS (SELECT 1 FROM tl_issue_transition existing WHERE existing.from_status_id=source.id AND existing.to_status_id=target.id AND existing.role_key='agent');

INSERT INTO tl_issue_transition (tstamp, from_status_id, to_status_id, role_key, require_public_comment, published)
SELECT UNIX_TIMESTAMP(), source.id, target.id, 'agent', 1, 1
FROM tl_issue_status source CROSS JOIN tl_issue_status target
WHERE source.status_key='triage' AND target.status_key='rejected'
AND NOT EXISTS (SELECT 1 FROM tl_issue_transition existing WHERE existing.from_status_id=source.id AND existing.to_status_id=target.id AND existing.role_key='agent');

INSERT INTO tl_issue_transition (tstamp, from_status_id, to_status_id, role_key, require_public_comment, published)
SELECT UNIX_TIMESTAMP(), source.id, target.id, 'agent', 1, 1
FROM tl_issue_status source CROSS JOIN tl_issue_status target
WHERE source.status_key='in_progress' AND target.status_key='waiting_user'
AND NOT EXISTS (SELECT 1 FROM tl_issue_transition existing WHERE existing.from_status_id=source.id AND existing.to_status_id=target.id AND existing.role_key='agent');

INSERT INTO tl_issue_transition (tstamp, from_status_id, to_status_id, role_key, require_public_comment, published)
SELECT UNIX_TIMESTAMP(), source.id, target.id, 'agent', 0, 1
FROM tl_issue_status source CROSS JOIN tl_issue_status target
WHERE source.status_key='waiting_user' AND target.status_key='in_progress'
AND NOT EXISTS (SELECT 1 FROM tl_issue_transition existing WHERE existing.from_status_id=source.id AND existing.to_status_id=target.id AND existing.role_key='agent');

INSERT INTO tl_issue_transition (tstamp, from_status_id, to_status_id, role_key, require_public_comment, published)
SELECT UNIX_TIMESTAMP(), source.id, target.id, 'agent', 1, 1
FROM tl_issue_status source CROSS JOIN tl_issue_status target
WHERE source.status_key='in_progress' AND target.status_key='resolved'
AND NOT EXISTS (SELECT 1 FROM tl_issue_transition existing WHERE existing.from_status_id=source.id AND existing.to_status_id=target.id AND existing.role_key='agent');

INSERT INTO tl_issue_transition (tstamp, from_status_id, to_status_id, role_key, require_public_comment, published)
SELECT UNIX_TIMESTAMP(), source.id, target.id, 'agent', 0, 1
FROM tl_issue_status source CROSS JOIN tl_issue_status target
WHERE source.status_key='resolved' AND target.status_key='closed'
AND NOT EXISTS (SELECT 1 FROM tl_issue_transition existing WHERE existing.from_status_id=source.id AND existing.to_status_id=target.id AND existing.role_key='agent');

INSERT INTO tl_issue_transition (tstamp, from_status_id, to_status_id, role_key, require_public_comment, published)
SELECT UNIX_TIMESTAMP(), source.id, target.id, 'agent', 1, 1
FROM tl_issue_status source CROSS JOIN tl_issue_status target
WHERE source.status_key='resolved' AND target.status_key='in_progress'
AND NOT EXISTS (SELECT 1 FROM tl_issue_transition existing WHERE existing.from_status_id=source.id AND existing.to_status_id=target.id AND existing.role_key='agent');

INSERT INTO tl_issue_transition (tstamp, from_status_id, to_status_id, role_key, require_public_comment, published)
SELECT UNIX_TIMESTAMP(), source.id, target.id, 'manager', 0, 1
FROM tl_issue_status source CROSS JOIN tl_issue_status target
WHERE source.status_key='new' AND target.status_key='triage'
AND NOT EXISTS (SELECT 1 FROM tl_issue_transition existing WHERE existing.from_status_id=source.id AND existing.to_status_id=target.id AND existing.role_key='manager');

INSERT INTO tl_issue_transition (tstamp, from_status_id, to_status_id, role_key, require_public_comment, published)
SELECT UNIX_TIMESTAMP(), source.id, target.id, 'manager', 0, 1
FROM tl_issue_status source CROSS JOIN tl_issue_status target
WHERE source.status_key='new' AND target.status_key='in_progress'
AND NOT EXISTS (SELECT 1 FROM tl_issue_transition existing WHERE existing.from_status_id=source.id AND existing.to_status_id=target.id AND existing.role_key='manager');

INSERT INTO tl_issue_transition (tstamp, from_status_id, to_status_id, role_key, require_public_comment, published)
SELECT UNIX_TIMESTAMP(), source.id, target.id, 'manager', 0, 1
FROM tl_issue_status source CROSS JOIN tl_issue_status target
WHERE source.status_key='triage' AND target.status_key='in_progress'
AND NOT EXISTS (SELECT 1 FROM tl_issue_transition existing WHERE existing.from_status_id=source.id AND existing.to_status_id=target.id AND existing.role_key='manager');

INSERT INTO tl_issue_transition (tstamp, from_status_id, to_status_id, role_key, require_public_comment, published)
SELECT UNIX_TIMESTAMP(), source.id, target.id, 'manager', 1, 1
FROM tl_issue_status source CROSS JOIN tl_issue_status target
WHERE source.status_key='triage' AND target.status_key='rejected'
AND NOT EXISTS (SELECT 1 FROM tl_issue_transition existing WHERE existing.from_status_id=source.id AND existing.to_status_id=target.id AND existing.role_key='manager');

INSERT INTO tl_issue_transition (tstamp, from_status_id, to_status_id, role_key, require_public_comment, published)
SELECT UNIX_TIMESTAMP(), source.id, target.id, 'manager', 1, 1
FROM tl_issue_status source CROSS JOIN tl_issue_status target
WHERE source.status_key='in_progress' AND target.status_key='waiting_user'
AND NOT EXISTS (SELECT 1 FROM tl_issue_transition existing WHERE existing.from_status_id=source.id AND existing.to_status_id=target.id AND existing.role_key='manager');

INSERT INTO tl_issue_transition (tstamp, from_status_id, to_status_id, role_key, require_public_comment, published)
SELECT UNIX_TIMESTAMP(), source.id, target.id, 'manager', 0, 1
FROM tl_issue_status source CROSS JOIN tl_issue_status target
WHERE source.status_key='waiting_user' AND target.status_key='in_progress'
AND NOT EXISTS (SELECT 1 FROM tl_issue_transition existing WHERE existing.from_status_id=source.id AND existing.to_status_id=target.id AND existing.role_key='manager');

INSERT INTO tl_issue_transition (tstamp, from_status_id, to_status_id, role_key, require_public_comment, published)
SELECT UNIX_TIMESTAMP(), source.id, target.id, 'manager', 1, 1
FROM tl_issue_status source CROSS JOIN tl_issue_status target
WHERE source.status_key='in_progress' AND target.status_key='resolved'
AND NOT EXISTS (SELECT 1 FROM tl_issue_transition existing WHERE existing.from_status_id=source.id AND existing.to_status_id=target.id AND existing.role_key='manager');

INSERT INTO tl_issue_transition (tstamp, from_status_id, to_status_id, role_key, require_public_comment, published)
SELECT UNIX_TIMESTAMP(), source.id, target.id, 'manager', 0, 1
FROM tl_issue_status source CROSS JOIN tl_issue_status target
WHERE source.status_key='resolved' AND target.status_key='closed'
AND NOT EXISTS (SELECT 1 FROM tl_issue_transition existing WHERE existing.from_status_id=source.id AND existing.to_status_id=target.id AND existing.role_key='manager');

INSERT INTO tl_issue_transition (tstamp, from_status_id, to_status_id, role_key, require_public_comment, published)
SELECT UNIX_TIMESTAMP(), source.id, target.id, 'manager', 1, 1
FROM tl_issue_status source CROSS JOIN tl_issue_status target
WHERE source.status_key='resolved' AND target.status_key='in_progress'
AND NOT EXISTS (SELECT 1 FROM tl_issue_transition existing WHERE existing.from_status_id=source.id AND existing.to_status_id=target.id AND existing.role_key='manager');

INSERT INTO tl_issue_transition (tstamp, from_status_id, to_status_id, role_key, require_public_comment, published)
SELECT UNIX_TIMESTAMP(), source.id, target.id, 'manager', 1, 1
FROM tl_issue_status source CROSS JOIN tl_issue_status target
WHERE source.status_key='closed' AND target.status_key='in_progress'
AND NOT EXISTS (SELECT 1 FROM tl_issue_transition existing WHERE existing.from_status_id=source.id AND existing.to_status_id=target.id AND existing.role_key='manager');

INSERT INTO tl_issue_transition (tstamp, from_status_id, to_status_id, role_key, require_public_comment, published)
SELECT UNIX_TIMESTAMP(), source.id, target.id, 'manager', 1, 1
FROM tl_issue_status source CROSS JOIN tl_issue_status target
WHERE source.status_key='rejected' AND target.status_key='triage'
AND NOT EXISTS (SELECT 1 FROM tl_issue_transition existing WHERE existing.from_status_id=source.id AND existing.to_status_id=target.id AND existing.role_key='manager');

INSERT INTO tl_issue_transition (tstamp, from_status_id, to_status_id, role_key, require_public_comment, published)
SELECT UNIX_TIMESTAMP(), source.id, target.id, 'member', 1, 1
FROM tl_issue_status source CROSS JOIN tl_issue_status target
WHERE source.status_key='waiting_user' AND target.status_key='in_progress'
AND NOT EXISTS (SELECT 1 FROM tl_issue_transition existing WHERE existing.from_status_id=source.id AND existing.to_status_id=target.id AND existing.role_key='member');

INSERT INTO tl_issue_transition (tstamp, from_status_id, to_status_id, role_key, require_public_comment, published)
SELECT UNIX_TIMESTAMP(), source.id, target.id, 'member', 0, 1
FROM tl_issue_status source CROSS JOIN tl_issue_status target
WHERE source.status_key='resolved' AND target.status_key='closed'
AND NOT EXISTS (SELECT 1 FROM tl_issue_transition existing WHERE existing.from_status_id=source.id AND existing.to_status_id=target.id AND existing.role_key='member');

INSERT INTO tl_issue_transition (tstamp, from_status_id, to_status_id, role_key, require_public_comment, published)
SELECT UNIX_TIMESTAMP(), source.id, target.id, 'member', 1, 1
FROM tl_issue_status source CROSS JOIN tl_issue_status target
WHERE source.status_key='resolved' AND target.status_key='in_progress'
AND NOT EXISTS (SELECT 1 FROM tl_issue_transition existing WHERE existing.from_status_id=source.id AND existing.to_status_id=target.id AND existing.role_key='member');

COMMIT;
