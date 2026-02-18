-- ============================================
-- SYSTEM PARAMETERS SEED
-- Based on fuel_planning_system_functional_spec_final_draft.pdf (A5)
-- and fuel_planning_implementation_qa_Claude_v3.pdf
-- ============================================

INSERT INTO system_parameters (parameter_name, parameter_value, description, data_type) VALUES

-- ============================================
-- PLANNING PARAMETERS
-- ============================================
('planning_horizon_days', '45', 'Days ahead to project and plan stock levels', 'int'),
('delivery_buffer_days', '2', 'Safety buffer days added to supplier delivery time', 'int'),
('critical_fill_pct', '40', 'Stock below this % triggers CATASTROPHE/CRITICAL urgency', 'float'),
('planned_fill_pct', '80', 'Target operating stock level % (order up to this)', 'float'),
('max_useful_volume_pct', '95', 'Do not fill tanks above this % (overfill risk)', 'float'),

-- ============================================
-- URGENCY THRESHOLDS (days left until stockout)
-- ============================================
('catastrophe_threshold_days', '0', 'Already below critical level → CATASTROPHE', 'int'),
('critical_threshold_days', '2', 'Days left ≤ this → CRITICAL', 'int'),
('must_order_threshold_days', '5', 'Days left ≤ this → MUST ORDER', 'int'),
('warning_threshold_days', '7', 'Days left ≤ this → WARNING', 'int'),

-- ============================================
-- ORDER PARAMETERS
-- ============================================
('order_step_tons', '60', 'Order granularity in tons (60 = 1 railway wagon)', 'int'),
('min_order_tons', '500', 'Minimum order size in tons', 'int'),
('supplier_priority_mode', 'COMPOSITE_SCORE', 'Supplier ranking mode: DELIVERY_TIME | COMPOSITE_SCORE | DELIVERY_WEIGHTED', 'string'),

-- ============================================
-- WORKING CAPITAL PARAMETERS
-- ============================================
('wc_enabled', '1', 'Enable Working Capital module (requires fuel costs to be set)', 'boolean'),
('opportunity_cost_rate', '8.0', 'Annual opportunity cost rate % for working capital calculations', 'float'),
('working_capital_currency', 'USD', 'Currency for working capital display', 'string'),

-- ============================================
-- ALERT PARAMETERS
-- ============================================
('stockout_warning_days', '5', 'Alert X days before projected stockout', 'int'),
('delivery_delay_tolerance', '3', 'Alert if delivery delay > X days vs planned', 'int'),
('consumption_anomaly_pct', '30', 'Alert if consumption > X% above normal', 'int')

ON DUPLICATE KEY UPDATE
    description = VALUES(description),
    data_type = VALUES(data_type);
-- Note: parameter_value is NOT updated on duplicate to preserve user customizations
