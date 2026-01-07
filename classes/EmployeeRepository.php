<?php

class EmployeeRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /* ================================
       BUILD WHERE CLAUSE
    ================================= */
    private function buildWhereClause(array $filters, array &$params): string
    {
        $conditions = [];

        if (!empty($filters['search'])) {
            $conditions[] = "(FirstName LIKE :search 
                              OR LastName LIKE :search 
                              OR CoopID LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['department'])) {
            $conditions[] = "Department = :department";
            $params[':department'] = $filters['department'];
        }

        if (!empty($filters['status'])) {
            $conditions[] = "Status = :status";
            $params[':status'] = $filters['status'];
        }

        return $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
    }

    /* ================================
       COUNT EMPLOYEES
    ================================= */
    public function countEmployees(array $filters = []): int
    {
        $params = [];
        $where = $this->buildWhereClause($filters, $params);

        $sql = "SELECT COUNT(*) FROM tblemployees $where";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    /* ================================
       GET EMPLOYEES (PAGINATED)
    ================================= */
    public function getEmployees(
        array $filters = [],
        int $limit = 20,
        int $offset = 0
    ): array {
        $params = [];
        $where = $this->buildWhereClause($filters, $params);

        $sql = "SELECT *, Department AS DepartmentName
                FROM tblemployees
                $where
                ORDER BY FirstName, LastName
                LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }

        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* ================================
       GET DEPARTMENTS
    ================================= */
    public function getDepartments(): array
    {
        $sql = "SELECT DISTINCT Department AS DepartmentName
                FROM tblemployees
                WHERE Department IS NOT NULL AND Department != ''
                ORDER BY Department";

        return $this->db
            ->query($sql)
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    /* ================================
       GENERATE NEXT COOP ID
    ================================= */
    public function generateNextCoopID(): string
    {
        $sql = "SELECT CoopID
                FROM tblemployees
                WHERE CoopID LIKE 'COOP-%'
                ORDER BY CoopID DESC
                LIMIT 1";

        $stmt = $this->db->query($sql);
        $last = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$last) {
            return 'COOP-00001';
        }

        $num = (int) substr($last['CoopID'], 5);
        return 'COOP-' . str_pad($num + 1, 5, '0', STR_PAD_LEFT);
    }
}