<x-feedback-layout>
    <div x-data="feedbackForm()" x-init="init()">
        {{-- Intro --}}
        <div class="text-center mb-8">
            <h1 class="text-2xl font-bold text-gray-900 mb-2">Wie war Ihr Erlebnis?</h1>
            <p class="text-gray-500 text-sm">Ihr Feedback hilft uns, unseren Support kontinuierlich zu verbessern.</p>
        </div>

        {{-- DSGVO-Hinweis --}}
        <div class="flex items-start gap-2 bg-blue-50 border border-blue-100 rounded-xl px-4 py-3 mb-6 text-sm text-blue-700">
            <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
            </svg>
            <span>Die Bewertung erfolgt <strong>anonym</strong> und kann nicht auf Personen oder Tickets zurückgeführt werden.</span>
        </div>

        <form action="{{ route('feedback.store') }}" method="POST" novalidate>
            @csrf
            {{-- Honeypot --}}
            <div class="hidden" aria-hidden="true">
                <input type="text" name="website" tabindex="-1" autocomplete="off">
            </div>

            {{-- Fragen --}}
            @php
                $questions = [
                    'q1_overall'         => 'Wie zufrieden waren Sie insgesamt mit unserem Service?',
                    'q2_processing_time' => 'Wie zufrieden waren Sie mit der Bearbeitungszeit?',
                    'q3_communication'   => 'Wie freundlich und verständlich war die Kommunikation?',
                    'q4_simplicity'      => 'Wie unkompliziert war die Unterstützung insgesamt?',
                    'q5_competence'      => 'Wie bewerten Sie die fachliche Kompetenz unseres Supports?',
                ];
                $smileys = [
                    1 => ['emoji' => '😠', 'label' => 'Sehr schlecht', 'on' => 'ring-red-500 bg-red-50',    'hover' => 'hover:bg-red-50'],
                    2 => ['emoji' => '🙁', 'label' => 'Schlecht',      'on' => 'ring-orange-400 bg-orange-50', 'hover' => 'hover:bg-orange-50'],
                    3 => ['emoji' => '😐', 'label' => 'Neutral',       'on' => 'ring-yellow-400 bg-yellow-50', 'hover' => 'hover:bg-yellow-50'],
                    4 => ['emoji' => '🙂', 'label' => 'Gut',           'on' => 'ring-lime-500 bg-lime-50',  'hover' => 'hover:bg-lime-50'],
                    5 => ['emoji' => '😄', 'label' => 'Sehr gut',      'on' => 'ring-green-500 bg-green-50', 'hover' => 'hover:bg-green-50'],
                ];
            @endphp

            <div class="space-y-5">
                @foreach($questions as $name => $label)
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                        <p class="font-medium text-gray-800 mb-4 text-sm leading-snug">{{ $loop->iteration }}. {{ $label }}</p>
                        <div class="flex justify-between gap-1 sm:gap-2">
                            @foreach($smileys as $value => $smiley)
                                <button
                                    type="button"
                                    @click="setScore('{{ $name }}', {{ $value }})"
                                    class="flex-1 flex flex-col items-center gap-1.5 py-3 px-1 rounded-xl border border-gray-200 ring-2 ring-transparent transition-all duration-150 cursor-pointer focus:outline-none {{ $smiley['hover'] }}"
                                    :class="{
                                        '{{ $smiley['on'] }}': scores['{{ $name }}'] === {{ $value }},
                                        'opacity-30 grayscale': scores['{{ $name }}'] !== null && scores['{{ $name }}'] !== {{ $value }}
                                    }"
                                    :aria-pressed="scores['{{ $name }}'] === {{ $value }}"
                                    aria-label="{{ $smiley['label'] }}"
                                    title="{{ $smiley['label'] }}"
                                >
                                    <span
                                        class="leading-none select-none transition-transform duration-150"
                                        :class="scores['{{ $name }}'] === {{ $value }} ? 'text-4xl' : 'text-3xl'"
                                    >{{ $smiley['emoji'] }}</span>
                                    <span class="text-xs text-gray-500 hidden sm:block leading-tight text-center">{{ $smiley['label'] }}</span>
                                </button>
                            @endforeach
                        </div>
                        <input type="hidden" :name="'{{ $name }}'" :value="scores['{{ $name }}']">
                        <p x-show="showErrors && scores['{{ $name }}'] === null"
                           class="mt-2 text-xs text-red-600">
                            Bitte wählen Sie eine Bewertung aus.
                        </p>
                    </div>
                @endforeach

                {{-- Freitextfeld --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                    <label for="comment" class="block font-medium text-gray-800 mb-2 text-sm">
                        Möchten Sie uns noch etwas mitteilen?
                        <span class="text-gray-400 font-normal ml-1">(freiwillig)</span>
                    </label>
                    <textarea
                        id="comment"
                        name="comment"
                        rows="3"
                        maxlength="2000"
                        placeholder="Ihr Kommentar…"
                        class="w-full border-gray-200 rounded-xl text-sm focus:border-indigo-400 focus:ring-indigo-400 resize-none"
                    >{{ old('comment') }}</textarea>
                </div>
            </div>

            {{-- Fehleranzeige (serverseitig) --}}
            @if ($errors->any())
                <div class="mt-4 bg-red-50 border border-red-200 rounded-xl px-4 py-3 text-sm text-red-700">
                    Bitte beantworten Sie alle Fragen, bevor Sie absenden.
                </div>
            @endif

            <div class="mt-6 flex justify-center">
                <button
                    type="submit"
                    @click="handleSubmit"
                    class="inline-flex items-center gap-2 px-8 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl shadow transition-colors duration-150 text-sm"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" />
                    </svg>
                    Bewertung absenden
                </button>
            </div>
        </form>
    </div>

    @push('scripts')
    <script>
        function feedbackForm() {
            return {
                scores: {
                    q1_overall: null,
                    q2_processing_time: null,
                    q3_communication: null,
                    q4_simplicity: null,
                    q5_competence: null,
                },
                showErrors: false,
                init() {
                    @if(old('q1_overall'))
                        this.scores.q1_overall = {{ (int) old('q1_overall', 0) }};
                    @endif
                    @if(old('q2_processing_time'))
                        this.scores.q2_processing_time = {{ (int) old('q2_processing_time', 0) }};
                    @endif
                    @if(old('q3_communication'))
                        this.scores.q3_communication = {{ (int) old('q3_communication', 0) }};
                    @endif
                    @if(old('q4_simplicity'))
                        this.scores.q4_simplicity = {{ (int) old('q4_simplicity', 0) }};
                    @endif
                    @if(old('q5_competence'))
                        this.scores.q5_competence = {{ (int) old('q5_competence', 0) }};
                    @endif
                    @if($errors->any())
                        this.showErrors = true;
                    @endif
                },
                setScore(question, value) {
                    this.scores[question] = value;
                },
                handleSubmit(e) {
                    const allAnswered = Object.values(this.scores).every(v => v !== null);
                    if (!allAnswered) {
                        e.preventDefault();
                        this.showErrors = true;
                        this.$el.closest('form').querySelectorAll('[x-show]')[0]?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                }
            }
        }
    </script>
    @endpush
</x-feedback-layout>
