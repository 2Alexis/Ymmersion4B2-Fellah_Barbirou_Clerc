<?php
// Activer l'affichage des erreurs
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config/database.php';

class PrinterScheduler {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function checkAndAssignPendingOrders() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id, filament_type 
                FROM orders 
                WHERE status = 'en_attente'
                ORDER BY created_at ASC
            ");
            $stmt->execute();
            $pendingOrders = $stmt->fetchAll();

            foreach ($pendingOrders as $order) {
                $stmt = $this->pdo->prepare("
                    SELECT p.* 
                    FROM printers p
                    LEFT JOIN printer_assignments pa ON p.id = pa.printer_id 
                        AND pa.status IN ('planifie', 'en_cours')
                    WHERE p.status = 'libre'
                    AND p.filament_type = ?
                    AND (pa.id IS NULL OR pa.end_time < NOW())
                    ORDER BY p.last_maintenance_date DESC
                    LIMIT 1
                ");
                
                $stmt->execute([$order['filament_type']]);
                $printer = $stmt->fetch();

                if ($printer) {
                    $this->assignPrinter($order['id'], 1);
                }
            }

            return true;
        } catch (Exception $e) {
            error_log("Erreur dans checkAndAssignPendingOrders: " . $e->getMessage());
            return false;
        }
    }

    public function assignPrinter($orderId, $estimatedDuration) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT filament_type, status, estimated_time 
                FROM orders 
                WHERE id = ?
            ");
            $stmt->execute([$orderId]);
            $order = $stmt->fetch();

            if (!$order) {
                throw new Exception("Commande non trouvée");
            }

            if ($order['status'] !== 'en_attente') {
                return false;
            }

            $estimatedDuration = $order['estimated_time'];

            $stmt = $this->pdo->prepare("
                SELECT p.* 
                FROM printers p
                LEFT JOIN printer_assignments pa ON p.id = pa.printer_id 
                    AND pa.status IN ('planifie', 'en_cours')
                WHERE p.status = 'libre'
                AND p.filament_type = ?
                AND (pa.id IS NULL OR pa.end_time < NOW())
                ORDER BY p.last_maintenance_date DESC
                LIMIT 1
            ");
            
            $stmt->execute([$order['filament_type']]);
            $printer = $stmt->fetch();

            if (!$printer) {
                return false;
            }

            $this->pdo->beginTransaction();

            try {
                $startTime = date('Y-m-d H:i:s');
                $endTime = date('Y-m-d H:i:s', strtotime("+{$estimatedDuration} minutes"));

                $stmt = $this->pdo->prepare("
                    INSERT INTO printer_assignments 
                    (order_id, printer_id, estimated_duration, start_time, end_time, status)
                    VALUES (?, ?, ?, ?, ?, 'planifie')
                ");
                
                $stmt->execute([
                    $orderId,
                    $printer['id'],
                    $estimatedDuration,
                    $startTime,
                    $endTime
                ]);

                $stmt = $this->pdo->prepare("
                    UPDATE printers 
                    SET status = 'en_cours'
                    WHERE id = ?
                ");
                $stmt->execute([$printer['id']]);

                $stmt = $this->pdo->prepare("
                    UPDATE orders 
                    SET status = 'en_cours'
                    WHERE id = ?
                ");
                $stmt->execute([$orderId]);

                $this->pdo->commit();
                return true;

            } catch (Exception $e) {
                $this->pdo->rollBack();
                error_log("Erreur lors de l'assignation: " . $e->getMessage());
                throw $e;
            }

        } catch (Exception $e) {
            error_log("Erreur dans assignPrinter: " . $e->getMessage());
            return false;
        }
    }

    public function updateAssignmentStatus($assignmentId, $newStatus) {
        try {
            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare("
                UPDATE printer_assignments 
                SET status = ?
                WHERE id = ?
            ");
            $stmt->execute([$newStatus, $assignmentId]);

            if ($newStatus === 'termine') {
                $stmt = $this->pdo->prepare("
                    SELECT printer_id, order_id 
                    FROM printer_assignments 
                    WHERE id = ?
                ");
                $stmt->execute([$assignmentId]);
                $assignment = $stmt->fetch(PDO::FETCH_ASSOC);

                $stmt = $this->pdo->prepare("
                    UPDATE printers 
                    SET status = 'libre'
                    WHERE id = ?
                ");
                $stmt->execute([$assignment['printer_id']]);

                $stmt = $this->pdo->prepare("
                    UPDATE orders 
                    SET status = 'termine'
                    WHERE id = ?
                ");
                $stmt->execute([$assignment['order_id']]);
            }

            $this->pdo->commit();
            return true;

        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Erreur dans updateAssignmentStatus: " . $e->getMessage());
            return false;
        }
    }

    public function getAvailablePrinters($filamentType = null) {
        try {
            $sql = "
                SELECT p.*, 
                    COALESCE(pa.end_time, 'Disponible') as next_available
                FROM printers p
                LEFT JOIN printer_assignments pa ON p.id = pa.printer_id 
                    AND pa.status IN ('planifie', 'en_cours')
                WHERE p.status = 'libre'
            ";
            
            $params = [];
            
            if ($filamentType) {
                $sql .= " AND p.filament_type = ?";
                $params[] = $filamentType;
            }
            
            $sql .= " ORDER BY p.last_maintenance_date DESC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Erreur dans getAvailablePrinters: " . $e->getMessage());
            return [];
        }
    }

    public function checkAndUpdateFinishedPrints() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT pa.id, pa.printer_id, pa.order_id
                FROM printer_assignments pa
                WHERE pa.status IN ('planifie', 'en_cours')
                AND pa.end_time <= NOW()
            ");
            $stmt->execute();
            $finishedAssignments = $stmt->fetchAll();

            foreach ($finishedAssignments as $assignment) {
                $this->updateAssignmentStatus($assignment['id'], 'termine');
            }

            return true;
        } catch (Exception $e) {
            error_log("Erreur dans checkAndUpdateFinishedPrints: " . $e->getMessage());
            return false;
        }
    }
}