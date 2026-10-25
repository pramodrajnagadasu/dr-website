<!-- Cancel Modal -->
<div class="modal-overlay" id="cancelModalOverlay">
  <div class="modal-box">
    <h3><i class="fas fa-times-circle" style="color:var(--red);"></i> Cancel Appointment</h3>
    <p class="modal-sub">This will notify the patient and doctor by email.</p>
    <div class="form-group" style="margin-bottom:0;">
      <label>Reason for cancellation</label>
      <textarea id="cancelReason" rows="3" placeholder="e.g. Doctor unavailable due to emergency" style="width:100%;padding:12px 14px;border:1px solid var(--border);border-radius:10px;font-size:14px;font-family:var(--font);resize:vertical;"></textarea>
      <p class="form-msg error" id="cancelErr" style="display:none;"></p>
    </div>
    <div class="modal-actions">
      <button type="button" class="btn-secondary" onclick="closeCancelModal()">Close</button>
      <button type="button" class="btn-danger" id="cancelSubmitBtn" onclick="submitCancel()">Cancel Appointment</button>
    </div>
  </div>
</div>

<!-- Postpone Modal -->
<div class="modal-overlay" id="postponeModalOverlay">
  <div class="modal-box">
    <h3><i class="fas fa-calendar-day" style="color:var(--gold);"></i> Postpone Appointment</h3>
    <p class="modal-sub">This will notify the patient and doctor by email.</p>
    <div class="form-group">
      <label>New Date</label>
      <input type="date" id="postponeDate" style="width:100%;padding:12px 14px;border:1px solid var(--border);border-radius:10px;font-size:14px;font-family:var(--font);" onchange="loadPostponeSlots()" />
    </div>
    <div class="form-group">
      <label>New Time Slot</label>
      <select id="postponeSlot"><option value="">Select a date first</option></select>
    </div>
    <div class="form-group" style="margin-bottom:0;">
      <label>Reason (optional)</label>
      <textarea id="postponeReason" rows="2" placeholder="e.g. Requested by patient" style="width:100%;padding:12px 14px;border:1px solid var(--border);border-radius:10px;font-size:14px;font-family:var(--font);resize:vertical;"></textarea>
      <p class="form-msg error" id="postponeErr" style="display:none;"></p>
    </div>
    <div class="modal-actions">
      <button type="button" class="btn-secondary" onclick="closePostponeModal()">Close</button>
      <button type="button" class="btn-primary" id="postponeSubmitBtn" onclick="submitPostpone()">Save New Date</button>
    </div>
  </div>
</div>

<!-- Close Case Modal -->
<div class="modal-overlay" id="closeModalOverlay">
  <div class="modal-box">
    <h3><i class="fas fa-check-circle" style="color:var(--green);"></i> Close Case</h3>

    <div id="closeFormView">
      <p class="modal-sub">Mark this appointment as closed.</p>
      <div class="form-group">
        <label>Reason</label>
        <select id="closeReason" onchange="toggleCloseOtherReason()" style="width:100%;padding:12px 14px;border:1px solid var(--border);border-radius:10px;font-size:14px;font-family:var(--font);">
          <option value="">Select a reason</option>
          <option value="Doctor saw the patient">Doctor saw the patient</option>
          <option value="Patient not visited">Patient not visited</option>
          <option value="Others">Others</option>
        </select>
      </div>
      <div class="form-group" id="closeOtherGroup" style="display:none;margin-bottom:0;">
        <label>Please specify</label>
        <textarea id="closeOtherReason" rows="2" placeholder="Enter reason" style="width:100%;padding:12px 14px;border:1px solid var(--border);border-radius:10px;font-size:14px;font-family:var(--font);resize:vertical;"></textarea>
      </div>
      <div class="form-group" id="closeFeeGroup" style="display:none;margin-bottom:0;">
        <label>Consultation Fee (₹, optional)</label>
        <input type="number" id="closeFeeAmount" min="0" step="1" placeholder="e.g. 500" style="width:100%;padding:12px 14px;border:1px solid var(--border);border-radius:10px;font-size:14px;font-family:var(--font);" />
        <p style="font-size:12px;color:var(--gray);margin-top:6px;">Leave blank to close without requesting payment. If set, the patient gets a payment link by email and can pay by UPI, debit or credit card from their portal.</p>
      </div>
      <p class="form-msg error" id="closeErr" style="display:none;"></p>
      <div class="modal-actions">
        <button type="button" class="btn-secondary" onclick="closeCloseModal()">Close</button>
        <button type="button" class="btn-primary" id="closeReviewBtn" onclick="reviewClose()">Review &amp; Close</button>
      </div>
    </div>

    <div id="closeConfirmView" style="display:none;">
      <p class="modal-sub">Please confirm these details before closing the case.</p>
      <div style="background:#f7f9fc;border-radius:10px;padding:14px 16px;font-size:14px;line-height:1.9;">
        <div><strong>Patient:</strong> <span id="confirmPatientName"></span></div>
        <div><strong>Reference:</strong> <span id="confirmRef"></span></div>
        <div><strong>Date &amp; Time:</strong> <span id="confirmDateSlot"></span></div>
        <div><strong>Reason:</strong> <span id="confirmReason"></span></div>
        <div><strong>Consultation Fee:</strong> <span id="confirmFee"></span></div>
      </div>
      <p class="form-msg error" id="closeConfirmErr" style="display:none;"></p>
      <div class="modal-actions">
        <button type="button" class="btn-secondary" onclick="backToCloseForm()">Back</button>
        <button type="button" class="btn-primary" id="closeSubmitBtn" onclick="submitClose()">Confirm &amp; Close</button>
      </div>
    </div>

  </div>
</div>
