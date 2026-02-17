-- Update fuel type densities with correct values from REV 2.0
UPDATE fuel_types SET density = 0.735 WHERE code = 'GAS92';
UPDATE fuel_types SET density = 0.830 WHERE code = 'DIESB7';
UPDATE fuel_types SET density = 0.535 WHERE code = 'GAZ';
UPDATE fuel_types SET density = 0.800 WHERE code = 'JET';
UPDATE fuel_types SET density = 0.740 WHERE code = 'MTBE';
UPDATE fuel_types SET density = 0.750 WHERE code = 'GAS95';
UPDATE fuel_types SET density = 0.760 WHERE code = 'GAS98';
UPDATE fuel_types SET density = 0.850 WHERE code = 'DIESB10';
UPDATE fuel_types SET density = 0.728 WHERE code = 'GAS80';
UPDATE fuel_types SET density = 0.735 WHERE code = 'GAS92EUR';
