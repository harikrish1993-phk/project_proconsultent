<div class="modal fade" id="scheduleInterviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="interviewForm" method="POST" action="handlers/interview_handler.php">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="can_code" value="<?= htmlspecialchars($id) ?>">
                <input type="hidden" name="token" value="<?= Auth::token() ?>">
                
                <div class="modal-header">
                    <h5 class="modal-title">Schedule Interview</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Date <span class="text-danger">*</span></label>
                            <input type="date" name="date" class="form-control" required 
                                   value="<?= date('Y-m-d', strtotime('+1 day')) ?>">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Time <span class="text-danger">*</span></label>
                            <input type="time" name="time" class="form-control" required 
                                   value="10:00">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Interview Type</label>
                            <select name="interview_type" class="form-select">
                                <option value="Technical" selected>Technical</option>
                                <option value="HR">HR Screening</option>
                                <option value="Manager">Hiring Manager</option>
                                <option value="Panel">Panel Interview</option>
                                <option value="Final">Final Round</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Location/Meeting Link</label>
                            <input type="text" name="location" class="form-control" 
                                   placeholder="Office address or Zoom/Teams link">
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">Interviewers</label>
                            <select name="interviewers[]" class="form-select select2-multiple" multiple="multiple">
                                <?php 
                                // Fetch interviewers (would be done in main page in production)
                                $mock_interviewers = [
                                    ['user_code' => 'REC001', 'full_name' => 'John Smith'],
                                    ['user_code' => 'REC002', 'full_name' => 'Sarah Johnson'],
                                    ['user_code' => 'REC003', 'full_name' => 'Michael Brown']
                                ];
                                foreach ($mock_interviewers as $interviewer): 
                                ?>
                                <option value="<?= htmlspecialchars($interviewer['user_code']) ?>">
                                    <?= htmlspecialchars($interviewer['full_name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Select team members who will conduct the interview</div>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="3" placeholder="Interview preparation notes, questions to ask, etc."></textarea>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select name="outcome" class="form-select">
                                <option value="Scheduled" selected>Scheduled</option>
                                <option value="Positive">Positive</option>
                                <option value="Negative">Negative</option>
                                <option value="Neutral">Neutral</option>
                                <option value="Rescheduled">Rescheduled</option>
                                <option value="Cancelled">Cancelled</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Schedule Interview</button>
                </div>
            </form>
        </div>
    </div>
</div>