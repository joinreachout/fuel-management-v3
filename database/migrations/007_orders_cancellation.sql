-- Migration 007: Add cancellation fields to orders table
-- Run in phpMyAdmin on d105380_fuelv3

ALTER TABLE orders
  ADD COLUMN cancelled_reason VARCHAR(500) NULL AFTER notes,
  ADD COLUMN cancelled_at DATETIME NULL AFTER cancelled_reason;

-- Verify
-- SELECT id, order_number, status, cancelled_reason, cancelled_at FROM orders LIMIT 5;
