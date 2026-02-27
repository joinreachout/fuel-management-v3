-- Migration 009: Crisis Resolution System — crisis_cases table
-- Date: 2026-02-25
--
-- Stores crisis resolution proposals (Split Delivery + Transfer) created
-- by the Procurement Advisor's Immediate Action workflow.
-- System proposes; humans decide and execute.

CREATE TABLE IF NOT EXISTS crisis_cases (
  id                    INT AUTO_INCREMENT PRIMARY KEY,

  -- Type of resolution proposed
  case_type             ENUM('split_delivery', 'transfer') NOT NULL,

  -- Lifecycle: proposed → accepted → monitoring → resolved
  status                ENUM('proposed', 'accepted', 'monitoring', 'resolved')
                        NOT NULL DEFAULT 'proposed',

  -- The depot in crisis (receiving side)
  receiving_depot_id    INT NOT NULL,
  fuel_type_id          INT NOT NULL,
  qty_needed_tons       DECIMAL(10,2) NOT NULL COMMENT 'Tons needed to reach target level',

  -- Donor side
  -- For split_delivery: the ERP order being partially redirected
  donor_order_id        INT NULL COMMENT 'ERP order whose delivery is split (split_delivery type)',
  -- For transfer: the depot giving away current stock
  donor_depot_id        INT NULL COMMENT 'Depot giving stock (transfer type)',

  -- Agreed split/transfer quantity
  split_qty_tons        DECIMAL(10,2) NOT NULL COMMENT 'Tons being redirected/transferred',

  -- Compensating Purchase Orders (created in step 4 of user flow)
  -- PO #1: for the critical (receiving) depot — covers gap not filled by split
  po_for_critical_id    INT NULL COMMENT 'PO for the critical depot (covers remaining gap)',
  -- PO #2: for the donor depot — replaces the volume it gave away
  po_for_donor_id       INT NULL COMMENT 'PO for the donor depot (compensates what it gave)',

  notes                 TEXT NULL,

  created_at            TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  resolved_at           TIMESTAMP NULL,

  FOREIGN KEY (receiving_depot_id) REFERENCES depots(id)     ON DELETE RESTRICT,
  FOREIGN KEY (fuel_type_id)       REFERENCES fuel_types(id) ON DELETE RESTRICT,
  FOREIGN KEY (donor_order_id)     REFERENCES orders(id)     ON DELETE SET NULL,
  FOREIGN KEY (donor_depot_id)     REFERENCES depots(id)     ON DELETE SET NULL,
  FOREIGN KEY (po_for_critical_id) REFERENCES orders(id)     ON DELETE SET NULL,
  FOREIGN KEY (po_for_donor_id)    REFERENCES orders(id)     ON DELETE SET NULL,

  INDEX idx_status           (status),
  INDEX idx_receiving_depot  (receiving_depot_id, fuel_type_id),
  INDEX idx_created_at       (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Crisis resolution proposals: split deliveries and depot transfers';
