-- Add grace_period_minutes to events table
ALTER TABLE events 
ADD COLUMN grace_period_minutes INT NOT NULL DEFAULT 20 
COMMENT 'Grace period in minutes after event ends for check-in (default 20 minutes)';

-- Add device_id to event_attendance table
ALTER TABLE event_attendance 
ADD COLUMN device_id VARCHAR(255) NULL 
COMMENT 'Device identifier used for check-in';

-- Add index for device lookup
ALTER TABLE event_attendance 
ADD INDEX idx_event_device (event_id, device_id);

-- Add admin_override flag to event_attendance
ALTER TABLE event_attendance 
ADD COLUMN admin_override TINYINT(1) DEFAULT 0 
COMMENT 'Flag indicating if check-in was done by admin override';

-- Add checked_in_by_admin field
ALTER TABLE event_attendance 
ADD COLUMN checked_in_by_admin VARCHAR(255) NULL 
COMMENT 'Admin username who manually checked in the user';

