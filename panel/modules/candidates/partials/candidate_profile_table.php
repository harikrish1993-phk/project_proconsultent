<?php if (!isset($candidate)) return; ?>

<div class="table-responsive">
    <table class="table table-borderless table-hover">
        <tbody>
            <tr><th>Name</th><td><?php echo htmlspecialchars($candidate['candidate_name']); ?></td></tr>
            <tr><th>Email</th><td><?php echo htmlspecialchars($candidate['email_id']); ?></td></tr>
            <tr><th>Alternate Email</th><td><?php echo htmlspecialchars($candidate['alternate_email_id'] ?? '-'); ?></td></tr>
            <tr><th>Contact</th><td><?php echo htmlspecialchars($candidate['contact_details'] ?? '-'); ?></td></tr>
            <tr><th>Alternate Contact</th><td><?php echo htmlspecialchars($candidate['alternate_contact_details'] ?? '-'); ?></td></tr>
            <tr><th>LinkedIn</th><td><a href="<?php echo htmlspecialchars($candidate['linkedin']); ?>" target="_blank"><?php echo htmlspecialchars($candidate['linkedin']); ?></a></td></tr>
            <tr><th>Role Addressed</th><td><?php echo htmlspecialchars($candidate['role_addressed'] ?? '-'); ?></td></tr>
            <tr><th>Current Position</th><td><?php echo htmlspecialchars($candidate['current_position'] ?? '-'); ?></td></tr>
            <tr><th>Experience</th><td><?php echo $candidate['experience'] ?? 0; ?> years</td></tr>
            <tr><th>Notice Period</th><td><?php echo $candidate['notice_period'] ?? 0; ?> days</td></tr>
            <tr><th>Current Location</th><td><?php echo htmlspecialchars($candidate['current_location'] ?? '-'); ?></td></tr>
            <tr><th>Preferred Location</th><td><?php echo htmlspecialchars($candidate['preferred_location'] ?? '-'); ?></td></tr>
            <tr><th>Current Employer</th><td><?php echo htmlspecialchars($candidate['current_employer'] ?? '-'); ?></td></tr>
            <tr><th>Current Agency</th><td><?php echo htmlspecialchars($candidate['current_agency'] ?? '-'); ?></td></tr>
            <tr><th>Current Salary</th><td><?php echo number_format($candidate['current_salary'] ?? 0, 2); ?></td></tr>
            <tr><th>Expected Salary</th><td><?php echo number_format($candidate['expected_salary'] ?? 0, 2); ?></td></tr>
            <tr><th>Can Join</th><td><?php echo $candidate['can_join'] ?? '-'; ?></td></tr>
            <tr><th>Current Daily Rate</th><td><?php echo number_format($candidate['current_daily_rate'] ?? 0, 2); ?></td></tr>
            <tr><th>Expected Daily Rate</th><td><?php echo number_format($candidate['expected_daily_rate'] ?? 0, 2); ?></td></tr>
            <tr><th>Working Status</th><td><?php echo htmlspecialchars($candidate['current_working_status'] ?? '-'); ?></td></tr>
            <tr><th>Languages</th><td><?php echo htmlspecialchars($candidate['languages'] ?? '-'); ?></td></tr>
            <tr><th>Lead Type</th><td><?php echo htmlspecialchars($candidate['lead_type'] ?? '-'); ?></td></tr>
            <tr><th>Lead Role</th><td><?php echo htmlspecialchars($candidate['lead_type_role'] ?? '-'); ?></td></tr>
            <tr><th>Work Auth</th><td><?php echo htmlspecialchars($candidate['work_auth_status'] ?? '-'); ?></td></tr>
            <tr><th>Follow Up</th><td><?php echo htmlspecialchars($candidate['follow_up'] ?? '-'); ?></td></tr>
            <tr><th>Follow Up Date</th><td><?php echo $candidate['follow_up_date'] ?? '-'; ?></td></tr>
            <tr><th>Face to Face</th><td><?php echo $candidate['face_to_face'] ?? '-'; ?></td></tr>
            <tr><th>Skills</th><td><?php echo htmlspecialchars($candidate['skill_set'] ?? '-'); ?></td></tr>
            <tr><th>Extra Details</th><td><?php echo nl2br(htmlspecialchars($candidate['extra_details'] ?? '-')); ?></td></tr>
            <!-- Dynamic -->
            <?php foreach ($dynamic as $name => $value): ?>
            <tr><th><?php echo htmlspecialchars($name); ?></th><td><?php echo htmlspecialchars($value); ?></td></tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>