<?php

namespace App\Livewire;

use App\Mail\BugReportMail;
use App\Models\Bug;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Livewire\Component;
use Livewire\WithFileUploads;

class ReportBug extends Component
{
    use WithFileUploads;
    
    public $isOpen = false;
    public $title = '';
    public $description = '';
    public $stepsToReproduce = '';
    public $screenshot = null;
    
    public function openModal()
    {
        $this->reset(['title', 'description', 'stepsToReproduce', 'screenshot']);
        $this->isOpen = true;
    }
    
    public function closeModal()
    {
        $this->isOpen = false;
    }
    
    public function submit()
    {
        $this->validate([
            'title' => 'required|string|min:5|max:255',
            'description' => 'required|string|min:10',
            'stepsToReproduce' => 'nullable|string',
            'screenshot' => 'nullable|image|max:5120', // 5MB max
        ]);
        
        // Get browser info
        $browserInfo = $_SERVER['HTTP_USER_AGENT'] ?? 'Неизвестен браузър';
        
        // Upload screenshot if provided
        $screenshotPath = null;
        if ($this->screenshot) {
            $screenshotPath = $this->screenshot->store('bug-screenshots', 'public');
        }
        
        // Create bug report
        $bug = Bug::create([
            'user_id' => Auth::id(),
            'title' => $this->title,
            'description' => $this->description,
            'steps_to_reproduce' => $this->stepsToReproduce,
            'browser_info' => $browserInfo,
            'screenshot_path' => $screenshotPath,
            'status' => 'open',
        ]);
        
        // Send email notification to admin
        $adminEmail = config('mail.from.address');
        Mail::to($adminEmail)->send(new BugReportMail($bug));
        
        // Close modal and show success message
        $this->closeModal();
        session()->flash('success', 'Благодарим! Докладът за грешка беше изпратен успешно.');
    }
    
    public function render()
    {
        return view('livewire.report-bug');
    }
}
