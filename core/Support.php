<?php
namespace Core;

use PDO;

class Support {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    // Create a new ticket
    public function createTicket($user_id, $subject, $message) {
        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("INSERT INTO tickets (user_id, subject, status) VALUES (?, ?, 'open')");
            $stmt->execute([$user_id, $subject]);
            $ticket_id = $this->db->lastInsertId();

            $stmtMsg = $this->db->prepare("INSERT INTO ticket_messages (ticket_id, user_id, message) VALUES (?, ?, ?)");
            $stmtMsg->execute([$ticket_id, $user_id, $message]);

            $this->db->commit();
            return $ticket_id;
        } catch (\Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    // Get user's tickets
    public function getUserTickets($user_id) {
        $stmt = $this->db->prepare("SELECT * FROM tickets WHERE user_id = ? ORDER BY id DESC");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll();
    }

    // Get ticket details with messages
    public function getTicketDetails($ticket_id) {
        $stmt = $this->db->prepare("
            SELECT t.*, u.name as customer_name, u.email as customer_email, u.wallet_balance, u.status as customer_status, r.name as customer_role
            FROM tickets t
            JOIN users u ON t.user_id = u.id
            JOIN roles r ON u.role_id = r.id
            WHERE t.id = ?
        ");
        $stmt->execute([$ticket_id]);
        $ticket = $stmt->fetch();

        if ($ticket) {
            $stmtMsg = $this->db->prepare("
                SELECT tm.*, u.name as author_name, r.name as role_name
                FROM ticket_messages tm
                JOIN users u ON tm.user_id = u.id
                JOIN roles r ON u.role_id = r.id
                WHERE tm.ticket_id = ? ORDER BY tm.id ASC
            ");
            $stmtMsg->execute([$ticket_id]);
            $ticket['messages'] = $stmtMsg->fetchAll();
        }
        return $ticket;
    }

    // Get all tickets for admin
    public function getAllTickets($filter_status = 'all') {
        $where_clause = '';
        if ($filter_status !== 'all') {
            $where_clause = "WHERE t.status = :status";
        }
        $query = "SELECT t.*, u.name as customer_name FROM tickets t JOIN users u ON t.user_id = u.id $where_clause ORDER BY t.id DESC";
        $stmt = $this->db->prepare($query);
        if ($filter_status !== 'all') {
            $stmt->bindParam(':status', $filter_status);
        }
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function closeTicket($ticket_id) {
        $stmt = $this->db->prepare("UPDATE tickets SET status = 'closed' WHERE id = ?");
        return $stmt->execute([$ticket_id]);
    }

    // Reply to a ticket
    public function replyToTicket($ticket_id, $user_id, $message) {
        $stmt = $this->db->prepare("INSERT INTO ticket_messages (ticket_id, user_id, message) VALUES (?, ?, ?)");
        if ($stmt->execute([$ticket_id, $user_id, $message])) {
            // Get user role
            $stmtRole = $this->db->prepare("SELECT role_id FROM users WHERE id = ?");
            $stmtRole->execute([$user_id]);
            $role_id = $stmtRole->fetchColumn();

            // Update ticket status based on who is replying
            if ($role_id == ROLE_ADMIN || $role_id == ROLE_MODERATOR) {
                $new_status = 'pending_customer';
            } else {
                $new_status = 'open';
            }
            $stmtUpdate = $this->db->prepare("UPDATE tickets SET status = ? WHERE id = ?");
            $stmtUpdate->execute([$new_status, $ticket_id]);
            return true;
        }
        return false;
    }
}
