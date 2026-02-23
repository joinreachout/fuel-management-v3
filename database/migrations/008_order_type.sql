-- Migration 008: Add order_type to separate Purchase Orders from ERP Orders
-- Date: 2026-02-23

-- 1. Add order_type column to distinguish PO vs ERP orders
ALTER TABLE orders
  ADD COLUMN order_type ENUM('purchase_order', 'erp_order') NOT NULL DEFAULT 'purchase_order'
    AFTER order_number,
  ADD COLUMN erp_order_id INT NULL AFTER cancelled_at,
  ADD COLUMN matched_at DATETIME NULL AFTER erp_order_id;

-- 2. Expand status ENUM to include 'matched' and 'expired' for PO lifecycle
ALTER TABLE orders MODIFY COLUMN status
  ENUM('planned', 'matched', 'expired', 'confirmed', 'in_transit', 'delivered', 'cancelled')
  NOT NULL DEFAULT 'planned';

-- 3. Classify existing data
-- Orders that already have ERP-driven statuses → mark as erp_order
UPDATE orders
  SET order_type = 'erp_order'
  WHERE status IN ('confirmed', 'in_transit', 'delivered');

-- planned and cancelled rows keep default 'purchase_order' — no action needed
