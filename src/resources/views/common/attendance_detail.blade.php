<form method="POST" action="{{ route("attendance.update", $attendance->id) }}">
    @csrf
    @method("PATCH")

    <input type="hidden" name="attendance_id" value="{{ $attendance->id }}" />
    <x-detail.table
        :rows="[
            ['label' => '氏名', 'value' => $user->name],
            ['label' => '日付', 'value' => $attendance->date->format('Y年m月d日')],
            [
                'label' => '出勤・退勤',
                'type' => 'time-range',
                'name' => ['clock_in', 'clock_out'],
                'value' => [$attendance->clock_in?->format('H:i'), $attendance->clock_out?->format('H:i')],
            ],
            [
                'label' => '休憩',
                'type' => 'time-range',
                'name' => ['break_in', 'break_out'],
                'value' => [$attendance->breakTimes->first()?->break_in?->format('H:i'),$attendance->breakTimes->first()?->break_out?->format('H:i')],
            ],
            ['label' => '備考', 'value' => $attendance->comment, 'name' => 'comment', 'type' => 'textarea'],
        ]"
        :button="[
            'text' => '修正',
            'type' => 'submit',
            'route' => auth()->user()->role === 'admin'
                ? route('admin.attendances.update', $attendance->id)
                : route('attendance.update', $attendance->id)
        ]"
        isEditable="true"
    />
</form>
