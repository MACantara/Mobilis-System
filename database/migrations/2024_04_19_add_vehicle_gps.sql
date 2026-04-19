-- Migration: Add GPS location columns to Vehicle table
-- Date: 2024-04-19
-- Description: Add latitude and longitude columns to Vehicle table for live tracking

USE mobilis_db;

-- Add GPS columns if they don't exist
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'mobilis_db' AND TABLE_NAME = 'Vehicle' AND COLUMN_NAME = 'latitude');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE Vehicle ADD COLUMN latitude DECIMAL(10,8) DEFAULT NULL AFTER mileage_km', 'SELECT ''Column latitude already exists'' AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'mobilis_db' AND TABLE_NAME = 'Vehicle' AND COLUMN_NAME = 'longitude');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE Vehicle ADD COLUMN longitude DECIMAL(11,8) DEFAULT NULL AFTER latitude', 'SELECT ''Column longitude already exists'' AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Update existing vehicles with sample GPS coordinates (Metro Manila area)
UPDATE Vehicle SET latitude = 14.6091, longitude = 121.0223 WHERE plate_number = 'ABC-1234';
UPDATE Vehicle SET latitude = 14.5764, longitude = 121.0851 WHERE plate_number = 'XYZ-5678';
UPDATE Vehicle SET latitude = 14.6349, longitude = 121.0330 WHERE plate_number = 'DEF-9012';
UPDATE Vehicle SET latitude = 14.5547, longitude = 121.0241 WHERE plate_number = 'GHI-3456';
UPDATE Vehicle SET latitude = 14.5995, longitude = 121.0586 WHERE plate_number = 'JKL-7890';
UPDATE Vehicle SET latitude = 14.6359, longitude = 121.0119 WHERE plate_number = 'MNO-2345';
UPDATE Vehicle SET latitude = 14.5794, longitude = 121.0358 WHERE plate_number = 'QRS-1007';
UPDATE Vehicle SET latitude = 14.6042, longitude = 120.9842 WHERE plate_number = 'TUV-1008';
UPDATE Vehicle SET latitude = 14.5869, longitude = 121.0637 WHERE plate_number = 'WXY-1009';
UPDATE Vehicle SET latitude = 14.5532, longitude = 121.0465 WHERE plate_number = 'ZAB-1010';
UPDATE Vehicle SET latitude = 14.6188, longitude = 121.0097 WHERE plate_number = 'CDE-1011';
UPDATE Vehicle SET latitude = 14.5485, longitude = 121.0682 WHERE plate_number = 'FGH-1012';
UPDATE Vehicle SET latitude = 14.5679, longitude = 120.9924 WHERE plate_number = 'IJK-1013';
UPDATE Vehicle SET latitude = 14.5917, longitude = 121.0726 WHERE plate_number = 'LMN-1014';
UPDATE Vehicle SET latitude = 14.6214, longitude = 121.0438 WHERE plate_number = 'OPQ-1015';
UPDATE Vehicle SET latitude = 14.5418, longitude = 121.0158 WHERE plate_number = 'RST-1016';
UPDATE Vehicle SET latitude = 14.5883, longitude = 121.0532 WHERE plate_number = 'UVW-1017';
UPDATE Vehicle SET latitude = 14.6087, longitude = 121.0289 WHERE plate_number = 'XYA-1018';
UPDATE Vehicle SET latitude = 14.5724, longitude = 121.0065 WHERE plate_number = 'BCD-1019';
UPDATE Vehicle SET latitude = 14.5956, longitude = 121.0409 WHERE plate_number = 'EFG-1020';
UPDATE Vehicle SET latitude = 14.5635, longitude = 121.0779 WHERE plate_number = 'HIJ-1021';
UPDATE Vehicle SET latitude = 14.6314, longitude = 121.0610 WHERE plate_number = 'KLM-1022';
UPDATE Vehicle SET latitude = 14.5821, longitude = 120.9976 WHERE plate_number = 'NOP-1023';
UPDATE Vehicle SET latitude = 14.5586, longitude = 121.0302 WHERE plate_number = 'QRT-1024';
UPDATE Vehicle SET latitude = 14.6159, longitude = 121.0193 WHERE plate_number = 'STU-1025';
UPDATE Vehicle SET latitude = 14.5492, longitude = 121.0665 WHERE plate_number = 'VWX-1026';
UPDATE Vehicle SET latitude = 14.6026, longitude = 121.0458 WHERE plate_number = 'YZA-1027';
UPDATE Vehicle SET latitude = 14.5751, longitude = 121.0128 WHERE plate_number = 'ABC-1028';
UPDATE Vehicle SET latitude = 14.5901, longitude = 121.0676 WHERE plate_number = 'DEF-1029';
UPDATE Vehicle SET latitude = 14.5523, longitude = 121.0499 WHERE plate_number = 'GHI-1030';
UPDATE Vehicle SET latitude = 14.6268, longitude = 121.0375 WHERE plate_number = 'JKL-1031';
UPDATE Vehicle SET latitude = 14.5457, longitude = 121.0593 WHERE plate_number = 'MNP-1032';
UPDATE Vehicle SET latitude = 14.6193, longitude = 121.0267 WHERE plate_number = 'QWE-1033';
UPDATE Vehicle SET latitude = 14.5664, longitude = 121.0805 WHERE plate_number = 'RTY-1034';
UPDATE Vehicle SET latitude = 14.5879, longitude = 120.9944 WHERE plate_number = 'UIO-1035';
UPDATE Vehicle SET latitude = 14.6075, longitude = 121.0702 WHERE plate_number = 'PAS-1036';
UPDATE Vehicle SET latitude = 14.5569, longitude = 121.0227 WHERE plate_number = 'DFG-1037';
UPDATE Vehicle SET latitude = 14.6242, longitude = 121.0548 WHERE plate_number = 'HJK-1038';
UPDATE Vehicle SET latitude = 14.5938, longitude = 121.0134 WHERE plate_number = 'LZX-1039';
UPDATE Vehicle SET latitude = 14.5716, longitude = 121.0441 WHERE plate_number = 'CVB-1040';
UPDATE Vehicle SET latitude = 14.6175, longitude = 121.0043 WHERE plate_number = 'NMK-1041';
UPDATE Vehicle SET latitude = 14.5401, longitude = 121.0609 WHERE plate_number = 'POI-1042';
UPDATE Vehicle SET latitude = 14.6008, longitude = 121.0321 WHERE plate_number = 'TRE-1043';
UPDATE Vehicle SET latitude = 14.5847, longitude = 121.0075 WHERE plate_number = 'WQA-1044';
UPDATE Vehicle SET latitude = 14.6482, longitude = 121.0485 WHERE plate_number = 'SED-1045';
UPDATE Vehicle SET latitude = 14.5598, longitude = 121.0753 WHERE plate_number = 'RFV-1046';
UPDATE Vehicle SET latitude = 14.6129, longitude = 121.0221 WHERE plate_number = 'TGB-1047';
UPDATE Vehicle SET latitude = 14.5367, longitude = 121.0402 WHERE plate_number = 'YHN-1048';

SELECT 'GPS columns added to Vehicle table and sample data updated' AS Status;
