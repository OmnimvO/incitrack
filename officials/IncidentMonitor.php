<?php

class IncidentReport
{
    private $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function getAllIncidents(): array
    {
        $query = "
            SELECT 
                ir.incident_id,
                c.category_name,
                ir.urgency_level,
                ir.details,
                ir.latitude,
                ir.longitude,
                ir.purok,
                ir.landmark,
                ir.reported_datetime,
                ir.status,
                ir.verified_datetime,
                COALESCE(bo.name, t.name) AS verified_by,
                r.fname AS reporter_fname,
                r.lname AS reporter_lname,
                (
                    SELECT COUNT(*) 
                    FROM incident_evidence ie 
                    WHERE ie.incident_id = ir.incident_id
                ) AS evidence_count
            FROM incident_reports ir
            JOIN categories c ON ir.category_id = c.category_id
            JOIN residents r ON ir.reporter_id = r.resident_id
            LEFT JOIN barangay_officials bo ON ir.verified_by = bo.user_id
            LEFT JOIN tanods t ON ir.verified_by = t.user_id
            ORDER BY 
                FIELD(ir.urgency_level, 'High', 'Medium', 'Low'),
                ir.reported_datetime DESC
        ";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
