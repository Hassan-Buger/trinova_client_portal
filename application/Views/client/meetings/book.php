<div class="tn-screen" style="max-width:960px">
    <div style="margin-bottom:24px">
        <h2 style="margin:0 0 6px;font-size:26px;font-weight:800;letter-spacing:-.02em">Book a Meeting with TriNova</h2>
        <p style="margin:0;color:#61756e;font-size:14.5px">Schedule a consultation or telephone call directly with your accounting team via Microsoft Bookings.</p>
    </div>

    <!-- Booking Options Grid -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:20px;margin-bottom:28px">
        <!-- Existing Client Meeting Card -->
        <div style="background:#fff;border-radius:24px;padding:28px;box-shadow:0 1px 2px rgba(16,54,45,.04),0 14px 34px -24px rgba(16,54,45,.4);display:flex;flex-direction:column;justify-content:space-between">
            <div>
                <div style="width:48px;height:48px;border-radius:14px;background:#dff1ee;color:#0d9488;display:flex;align-items:center;justify-content:center;margin-bottom:18px">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                </div>
                <h3 style="margin:0 0 8px;font-size:20px;font-weight:800;color:#213330">Existing Client Review Meeting</h3>
                <p style="margin:0 0 20px;color:#61756e;font-size:14px;line-height:1.5">
                    In-depth annual accounts review, tax planning consultation, or business advice session (45 minutes).
                </p>
            </div>
            <form action="/client/meetings/book" method="POST" data-ajax-form style="margin:0">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\Application\Core\Session::csrfToken()) ?>">
                <input type="hidden" name="meeting_type" value="existing_client_meeting">
                <button type="submit" style="width:100%;background:#0d9488;color:#fff;border:none;padding:14px;border-radius:14px;font-weight:700;font-size:14.5px;cursor:pointer;box-shadow:0 8px 18px -8px rgba(13,148,136,.7)">
                    Book Client Review &rarr;
                </button>
            </form>
        </div>

        <!-- Telephone Call Card -->
        <div style="background:#fff;border-radius:24px;padding:28px;box-shadow:0 1px 2px rgba(16,54,45,.04),0 14px 34px -24px rgba(16,54,45,.4);display:flex;flex-direction:column;justify-content:space-between">
            <div>
                <div style="width:48px;height:48px;border-radius:14px;background:#fdecdc;color:#e07d24;display:flex;align-items:center;justify-content:center;margin-bottom:18px">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                    </svg>
                </div>
                <h3 style="margin:0 0 8px;font-size:20px;font-weight:800;color:#213330">Quick Telephone Call</h3>
                <p style="margin:0 0 20px;color:#61756e;font-size:14px;line-height:1.5">
                    Quick phone query regarding payroll, VAT calculation, or urgent accounting question (15 minutes).
                </p>
            </div>
            <form action="/client/meetings/book" method="POST" data-ajax-form style="margin:0">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\Application\Core\Session::csrfToken()) ?>">
                <input type="hidden" name="meeting_type" value="telephone_call">
                <button type="submit" style="width:100%;background:#ef8f3c;color:#fff;border:none;padding:14px;border-radius:14px;font-weight:700;font-size:14.5px;cursor:pointer;box-shadow:0 8px 16px -8px rgba(239,143,60,.9)">
                    Book Quick Call &rarr;
                </button>
            </form>
        </div>
    </div>

    <!-- Booking History -->
    <div style="background:#fff;border-radius:24px;padding:26px;box-shadow:0 1px 2px rgba(16,54,45,.04),0 14px 34px -24px rgba(16,54,45,.4)">
        <h3 style="margin:0 0 16px;font-size:18px;font-weight:800;color:#213330">Recent Booking Requests</h3>
        <?php if (empty($meetings)): ?>
            <div style="color:#8a9a94;font-size:14px">No previous meeting bookings recorded.</div>
        <?php else: ?>
            <div style="overflow-x:auto">
                <table style="width:100%;border-collapse:collapse;text-align:left">
                    <thead>
                        <tr style="border-bottom:2px solid #eef4f1;color:#7d8e88;font-size:12.5px;text-transform:uppercase;letter-spacing:.05em">
                            <th style="padding:12px 16px;font-weight:700">Meeting Type</th>
                            <th style="padding:12px 16px;font-weight:700">Booking Reference</th>
                            <th style="padding:12px 16px;font-weight:700">Date Requested</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($meetings as $m): ?>
                            <tr style="border-bottom:1px solid #eef4f1">
                                <td style="padding:16px;font-weight:700;color:#213330">
                                    <?= $m['type'] === 'existing_client_meeting' ? 'Existing Client Review' : 'Telephone Call' ?>
                                </td>
                                <td style="padding:16px;color:#0d9488;font-weight:700;font-family:monospace;font-size:14px">
                                    <?= htmlspecialchars($m['external_booking_reference']) ?>
                                </td>
                                <td style="padding:16px;color:#7d8e88;font-size:13.5px">
                                    <?= date('d M Y, H:i', strtotime($m['created_at'])) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
