<?php

namespace Application\Models;

use Application\Core\Model;

class OtpChallenge extends Model
{
    public const ACTIVATION = 'client_account_activation';
    public const PASSWORD_RESET = 'password_reset';

    public function issue(int $userId, string $email, string $purpose, bool $force = false): array
    {
        $latest = $this->latest($userId, $purpose);
        if (!$force && $latest && strtotime((string)$latest['created_at']) > time() - 60) {
            return ['ok' => false, 'reason' => 'cooldown', 'retry_after' => max(1, 60 - (time() - strtotime($latest['created_at'])))];
        }
        $this->invalidate($userId, $purpose);
        $code = (string)random_int(100000, 999999);
        $stmt = $this->db->prepare("INSERT INTO otp_challenges (user_id,email,purpose,otp_hash,expires_at) VALUES (:user_id,:email,:purpose,:otp_hash,DATE_ADD(NOW(), INTERVAL 10 MINUTE))");
        $stmt->execute(['user_id'=>$userId,'email'=>strtolower($email),'purpose'=>$purpose,'otp_hash'=>password_hash($code, PASSWORD_DEFAULT)]);
        return ['ok'=>true,'id'=>(int)$this->db->lastInsertId(),'code'=>$code];
    }

    public function verify(int $userId, string $email, string $purpose, string $code): string
    {
        $row = $this->latest($userId, $purpose);
        if (!$row || $row['used_at'] !== null) return 'invalid';
        if (strtotime($row['expires_at']) <= time()) return 'expired';
        if ((int)$row['attempt_count'] >= 5) return 'locked';
        if (!hash_equals(strtolower((string)$row['email']), strtolower($email)) || !preg_match('/^\d{6}$/', $code) || !password_verify($code, $row['otp_hash'])) {
            $stmt=$this->db->prepare("UPDATE otp_challenges SET attempt_count=attempt_count+1 WHERE id=:id"); $stmt->execute(['id'=>$row['id']]);
            return ((int)$row['attempt_count'] + 1 >= 5) ? 'locked' : 'invalid';
        }
        $stmt=$this->db->prepare("UPDATE otp_challenges SET used_at=NOW() WHERE id=:id AND used_at IS NULL"); $stmt->execute(['id'=>$row['id']]);
        return $stmt->rowCount() === 1 ? 'verified' : 'invalid';
    }

    public function invalidate(int $userId, string $purpose): void
    {
        $stmt=$this->db->prepare("UPDATE otp_challenges SET used_at=COALESCE(used_at,NOW()) WHERE user_id=:user_id AND purpose=:purpose AND used_at IS NULL");
        $stmt->execute(['user_id'=>$userId,'purpose'=>$purpose]);
    }

    private function latest(int $userId, string $purpose): ?array
    {
        $stmt=$this->db->prepare("SELECT * FROM otp_challenges WHERE user_id=:user_id AND purpose=:purpose ORDER BY id DESC LIMIT 1");
        $stmt->execute(['user_id'=>$userId,'purpose'=>$purpose]); return $stmt->fetch() ?: null;
    }
}
