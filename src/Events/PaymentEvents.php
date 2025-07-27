<?php

namespace GesPrender\Events;

/**
 * Evento disparado cuando un estudiante tiene un pago pendiente
 */
class PaymentDueEvent extends ModuleEvent
{
    public function getStudent(): array
    {
        return $this->get('student', []);
    }
    
    public function getStudentId(): ?int
    {
        return $this->get('student')['id'] ?? null;
    }
    
    public function getAmount(): float
    {
        return (float) $this->get('amount', 0);
    }
    
    public function getConcept(): string
    {
        return $this->get('concept', '');
    }
    
    public function getDueDate(): ?string
    {
        return $this->get('due_date');
    }
    
    public function getCurrency(): string
    {
        return $this->get('currency', 'USD');
    }
    
    public function getMetadata(): array
    {
        return $this->get('metadata', []);
    }
}

/**
 * Evento disparado cuando se confirma un pago
 */
class PaymentConfirmedEvent extends ModuleEvent
{
    public function getPaymentId(): ?string
    {
        return $this->get('payment_id');
    }
    
    public function getStudentId(): ?int
    {
        return $this->get('student_id');
    }
    
    public function getAmount(): float
    {
        return (float) $this->get('amount', 0);
    }
    
    public function getOriginModule(): ?string
    {
        return $this->get('module_origin');
    }
    
    public function getTransactionId(): ?string
    {
        return $this->get('transaction_id');
    }
    
    public function getPaymentMethod(): ?string
    {
        return $this->get('payment_method');
    }
    
    public function getPaymentDate(): ?string
    {
        return $this->get('payment_date');
    }
}

/**
 * Evento disparado cuando falla un pago
 */
class PaymentFailedEvent extends ModuleEvent
{
    public function getError(): string
    {
        return $this->get('error', '');
    }
    
    public function getStudentId(): ?int
    {
        return $this->get('student_id');
    }
    
    public function getAmount(): float
    {
        return (float) $this->get('amount', 0);
    }
    
    public function getErrorCode(): ?string
    {
        return $this->get('error_code');
    }
    
    public function getRetryable(): bool
    {
        return (bool) $this->get('retryable', false);
    }
}

/**
 * Evento disparado cuando un pago está pendiente de confirmación
 */
class PaymentPendingEvent extends ModuleEvent
{
    public function getPaymentId(): ?string
    {
        return $this->get('payment_id');
    }
    
    public function getStudentId(): ?int
    {
        return $this->get('student_id');
    }
    
    public function getAmount(): float
    {
        return (float) $this->get('amount', 0);
    }
    
    public function getPaymentUrl(): ?string
    {
        return $this->get('payment_url');
    }
    
    public function getExpiresAt(): ?string
    {
        return $this->get('expires_at');
    }
}

/**
 * Evento disparado cuando se inscribe un estudiante
 */
class StudentEnrolledEvent extends ModuleEvent
{
    public function getStudent(): array
    {
        return $this->get('student', []);
    }
    
    public function getStudentId(): ?int
    {
        return $this->get('student')['id'] ?? null;
    }
    
    public function getCourse(): array
    {
        return $this->get('course', []);
    }
    
    public function getMonthlyFee(): float
    {
        return (float) $this->get('monthly_fee', 0);
    }
    
    public function getEnrollmentDate(): ?string
    {
        return $this->get('enrollment_date');
    }
}

/**
 * Evento disparado cuando un estudiante se da de baja
 */
class StudentDroppedEvent extends ModuleEvent
{
    public function getStudentId(): ?int
    {
        return $this->get('student_id');
    }
    
    public function getDropDate(): ?string
    {
        return $this->get('drop_date');
    }
    
    public function getReason(): ?string
    {
        return $this->get('reason');
    }
    
    public function getCancelPendingPayments(): bool
    {
        return (bool) $this->get('cancel_pending_payments', true);
    }
}

/**
 * Evento para enviar emails
 */
class SendEmailEvent extends ModuleEvent
{
    public function getTo(): string
    {
        return $this->get('to', '');
    }
    
    public function getTemplate(): string
    {
        return $this->get('template', '');
    }
    
    public function getData(): array
    {
        return $this->get('data', []);
    }
    
    public function getSubject(): ?string
    {
        return $this->get('subject');
    }
    
    public function getFrom(): ?string
    {
        return $this->get('from');
    }
    
    public function getAttachments(): array
    {
        return $this->get('attachments', []);
    }
} 