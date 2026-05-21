    /***********************
     * Calendar App Script *
     ***********************/

    (function(){
      // categories & colors
      const CATEGORIES = [
        {id:'work', label:'Work', color:'#007bff'},
        {id:'meeting', label:'Meeting', color:'#ff8a00'},
        {id:'personal', label:'Personal', color:'#00a884'},
        {id:'deadline', label:'Deadline', color:'#e53935'},
        {id:'announcement', label:'Announcement', color:'#9c27b0'}, // NEW CATEGORY
        {id:'others', label:'Other', color:'#6c757d'}
      ];

      const STORAGE_KEY = 'emp_calendar_events_v2';
      const IMPORTED_EVENTS_KEY = 'imported_announcements_v1';
      const REMINDER_OFFSET_MIN = 5; // minutes before

      // UI elements
      const calendarGrid = document.getElementById('calendarGrid');
      const monthYearLabel = document.getElementById('monthYearLabel');
      const prevBtn = document.getElementById('prevBtn');
      const nextBtn = document.getElementById('nextBtn');
      const todayBtn = document.getElementById('todayBtn');
      const addQuickBtn = document.getElementById('addQuickBtn');
      const modalBackdrop = document.getElementById('modalBackdrop');
      const modalTitle = document.getElementById('modalTitle');
      const evtTitle = document.getElementById('evtTitle');
      const evtDate = document.getElementById('evtDate');
      const evtTime = document.getElementById('evtTime');
      const evtCategory = document.getElementById('evtCategory');
      const evtDetails = document.getElementById('evtDetails');
      const saveBtn = document.getElementById('saveBtn');
      const cancelBtn = document.getElementById('cancelBtn');
      const deleteBtn = document.getElementById('deleteBtn');
      const upcomingList = document.getElementById('upcomingList');
      const legend = document.getElementById('legend');
      const importedEventsList = document.getElementById('importedEventsList');
      const noImportedEvents = document.getElementById('noImportedEvents');
      const importedEventsSection = document.getElementById('importedEventsSection');

      // 🔔 Notification elements - SAME as Dashboard
      const notifBtn = document.getElementById("notifBtn");
      const notifDropdown = document.getElementById("notifDropdown");
      const notifModal = document.getElementById("notifModal");
      const viewAllNotif = document.getElementById("viewAllNotif");
      const closeNotifModal = document.getElementById("closeNotifModal");

      let currentViewDate = new Date(); // month to show
      let events = []; // loaded from storage
      let importedAnnouncements = []; // imported from Activity page
      let editingEventId = null; // id of event being edited
      let reminderTimers = {}; // id -> timeoutId

      /* ---- helper utils ---- */
      function uid(){ return String(Date.now()) + Math.floor(Math.random()*1000); }
      function dateToYMD(d){
        const yyyy = d.getFullYear();
        const mm = String(d.getMonth()+1).padStart(2,'0');
        const dd = String(d.getDate()).padStart(2,'0');
        return `${yyyy}-${mm}-${dd}`;
      }
      function datetimeFor(dateStr,timeStr){
        if(!timeStr) return dateStr; // date only (allDay)
        // produce ISO-like local: YYYY-MM-DDTHH:MM
        return dateStr + 'T' + timeStr;
      }
      function loadEvents(){
        try{
          const raw = localStorage.getItem(STORAGE_KEY);
          events = raw ? JSON.parse(raw) : [];
        }catch(e){ events = []; }
      }
      function saveEvents(){
        localStorage.setItem(STORAGE_KEY, JSON.stringify(events));
      }
      function loadImportedAnnouncements(){
        try{
          const raw = localStorage.getItem(IMPORTED_EVENTS_KEY);
          importedAnnouncements = raw ? JSON.parse(raw) : [];
        }catch(e){ importedAnnouncements = []; }
      }
      function saveImportedAnnouncements(){
        localStorage.setItem(IMPORTED_EVENTS_KEY, JSON.stringify(importedAnnouncements));
      }

      /* ---- Import Announcements from Activity ---- */
      function importAnnouncementFromActivity(title, date, description) {
        // Check if already imported
        const alreadyImported = importedAnnouncements.find(ann => 
          ann.title === title && ann.date === date
        );
        
        if (alreadyImported) {
          alert(`"${title}" is already in your calendar!`);
          return false;
        }

        // Create imported announcement object
        const announcement = {
          id: uid(),
          title: title,
          date: date,
          description: description || '',
          importedDate: new Date().toISOString(),
          source: 'activity'
        };
        
        importedAnnouncements.push(announcement);
        saveImportedAnnouncements();
        
        // Also add to calendar events
        const calendarEvent = {
          id: announcement.id,
          title: title,
          start: date + 'T09:00', // Default time 9:00 AM
          category: 'announcement',
          color: '#9c27b0',
          details: description || '',
          timeDisplay: '09:00',
          isImported: true
        };
        
        events.push(calendarEvent);
        saveEvents();
        
        renderCalendar();
        renderUpcoming();
        renderImportedEvents();
        scheduleAllReminders();
        
        return true;
      }

      /* ---- Check for incoming imports ---- */
      function checkForNewImports() {
        // Check URL parameters for imported event
        const urlParams = new URLSearchParams(window.location.search);
        const importedTitle = urlParams.get('import_title');
        const importedDate = urlParams.get('import_date');
        const importedDesc = urlParams.get('import_desc');
        
        if (importedTitle && importedDate) {
          // Clean URL
          window.history.replaceState({}, document.title, window.location.pathname);
          
          // Import the event
          const success = importAnnouncementFromActivity(
            decodeURIComponent(importedTitle),
            importedDate,
            importedDesc ? decodeURIComponent(importedDesc) : ''
          );
          
          if (success) {
            alert(`✅ "${decodeURIComponent(importedTitle)}" added to calendar!`);
          }
        }
      }

      /* ---- render imported events ---- */
      function renderImportedEvents() {
        importedEventsList.innerHTML = '';
        
        if (importedAnnouncements.length === 0) {
          noImportedEvents.style.display = 'block';
          return;
        }
        
        noImportedEvents.style.display = 'none';
        
        // Sort by date (newest first)
        const sorted = [...importedAnnouncements].sort((a, b) => 
          new Date(b.importedDate) - new Date(a.importedDate)
        ).slice(0, 5); // Show only 5 most recent
        
        sorted.forEach(ann => {
          const item = document.createElement('div');
          item.className = 'imported-event-item';
          
          // Format date for display
          const dateObj = new Date(ann.date);
          const formattedDate = dateObj.toLocaleDateString('en-US', { 
            month: 'short', 
            day: 'numeric',
            year: 'numeric'
          });
          
          item.innerHTML = `
            <div class="event-title">${ann.title}</div>
            <div class="event-date">${formattedDate}</div>
          `;
          
          item.addEventListener('click', () => {
            // Find and highlight the date in calendar
            const dateParts = ann.date.split('-');
            currentViewDate = new Date(dateParts[0], dateParts[1] - 1, dateParts[2]);
            renderCalendar();
            
            // Scroll to today
            setTimeout(() => {
              const todayCell = document.querySelector('.cell.today');
              if (todayCell) {
                todayCell.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
              }
            }, 100);
          });
          
          importedEventsList.appendChild(item);
        });
        
        // Add "View All" if more than 5
        if (importedAnnouncements.length > 5) {
          const viewAll = document.createElement('div');
          viewAll.style.textAlign = 'center';
          viewAll.style.marginTop = '10px';
          viewAll.innerHTML = `<a href="#" id="viewAllImported" style="color:#00a884;font-size:12px">View all ${importedAnnouncements.length} imported events</a>`;
          importedEventsList.appendChild(viewAll);
          
          document.getElementById('viewAllImported').addEventListener('click', (e) => {
            e.preventDefault();
            showAllImportedEvents();
          });
        }
      }

      /* ---- show all imported events modal ---- */
      function showAllImportedEvents() {
        const modal = document.createElement('div');
        modal.className = 'modal-backdrop active';
        modal.innerHTML = `
          <div class="modal" style="width: 500px;">
            <h3>All Imported Announcements</h3>
            <div style="max-height: 400px; overflow-y: auto; margin-bottom: 15px;">
              ${importedAnnouncements.map(ann => {
                const date = new Date(ann.date);
                const importDate = new Date(ann.importedDate);
                return `
                  <div style="background: #f8f9fa; padding: 12px; border-radius: 6px; margin-bottom: 8px; border-left: 4px solid #9c27b0;">
                    <div style="font-weight: 600; color: #333;">${ann.title}</div>
                    <div style="font-size: 13px; color: #666; margin-top: 4px;">${ann.description || 'No description'}</div>
                    <div style="display: flex; justify-content: space-between; margin-top: 8px; font-size: 12px; color: #888;">
                      <span>Event Date: ${date.toLocaleDateString()}</span>
                      <span>Imported: ${importDate.toLocaleDateString()}</span>
                    </div>
                  </div>
                `;
              }).join('')}
            </div>
            <button class="btn save" id="closeImportedModal" style="width: 100%;">Close</button>
          </div>
        `;
        
        document.body.appendChild(modal);
        
        modal.addEventListener('click', (e) => {
          if (e.target === modal || e.target.id === 'closeImportedModal') {
            document.body.removeChild(modal);
          }
        });
      }

      /* ---- render legend ---- */
      function renderLegend(){
        legend.innerHTML = '';
        CATEGORIES.forEach(c=>{
          const chip = document.createElement('div');
          chip.className = 'chip';
          chip.innerHTML = `<span style="width:12px;height:12px;background:${c.color};border-radius:4px;display:inline-block"></span><span>${c.label}</span>`;
          legend.appendChild(chip);
        });
      }

      /* ---- calendar rendering ---- */
      function startOfMonth(d){ return new Date(d.getFullYear(), d.getMonth(), 1); }
      function renderCalendar(){
        // Clear previous day cells (keep the weekday headers present)
        while(calendarGrid.children.length > 7) calendarGrid.removeChild(calendarGrid.lastChild);

        const year = currentViewDate.getFullYear();
        const month = currentViewDate.getMonth();

        const firstDay = new Date(year, month, 1).getDay();
        const daysInMonth = new Date(year, month + 1, 0).getDate();

        monthYearLabel.textContent = currentViewDate.toLocaleString(undefined,{month:'long', year:'numeric'});

        // preceding empty cells
        for(let i=0;i<firstDay;i++){
          const cell = document.createElement('div');
          cell.className = 'cell empty';
          calendarGrid.appendChild(cell);
        }

        // create day cells
        for(let day=1; day<=daysInMonth; day++){
          const dateStr = `${year}-${String(month+1).padStart(2,'0')}-${String(day).padStart(2,'0')}`;
          const cell = document.createElement('div');
          cell.className = 'cell';
          cell.dataset.date = dateStr;

          const today = new Date();
          if(day === today.getDate() && month === today.getMonth() && year === today.getFullYear()){
            cell.classList.add('today');
          }

          // date number
          const num = document.createElement('div');
          num.className = 'date-num';
          num.textContent = day;
          cell.appendChild(num);

          // events container
          const evtsContainer = document.createElement('div');
          evtsContainer.className = 'events';

          // find events for this date
          const dayEvents = events.filter(ev => {
            const startDate = (ev.start||'').split('T')[0];
            return startDate === dateStr;
          }).sort((a,b)=> {
            const at = a.start.includes('T') ? a.start : a.start + 'T00:00';
            const bt = b.start.includes('T') ? b.start : b.start + 'T00:00';
            return new Date(at) - new Date(bt);
          });

          dayEvents.slice(0,3).forEach(ev=>{
            const badge = document.createElement('div');
            badge.className = 'evt-badge';
            badge.textContent = (ev.timeDisplay ? ev.timeDisplay + ' • ' : '') + ev.title;
            badge.style.background = ev.color || '#888';
            badge.title = ev.title + (ev.details ? ' — ' + ev.details : '');
            badge.dataset.id = ev.id;
            
            // Different icon for imported announcements
            if (ev.category === 'announcement' && ev.isImported) {
              badge.innerHTML = `<i class="fas fa-bullhorn" style="margin-right: 4px;"></i> ${badge.textContent}`;
            }
            
            badge.addEventListener('click', (e)=>{
              e.stopPropagation();
              openEditModal(ev.id);
            });
            evtsContainer.appendChild(badge);
          });

          if(dayEvents.length > 3){
            const more = document.createElement('div');
            more.className = 'evt-badge';
            more.style.background = '#ececec';
            more.style.color = '#333';
            more.textContent = `+${dayEvents.length - 3} more`;
            more.title = 'Click to view all';
            more.addEventListener('click', (e)=>{
              e.stopPropagation();
              openAddModal(dateStr);
            });
            evtsContainer.appendChild(more);
          }

          cell.appendChild(evtsContainer);

          // clicking blank area opens add modal
          cell.addEventListener('click', (e)=>{
            if(e.target.classList.contains('evt-badge')) return;
            openAddModal(dateStr);
          });

          calendarGrid.appendChild(cell);
        }
      }

      /* ---- upcoming list ---- */
      function renderUpcoming(){
        upcomingList.innerHTML = '';
        const now = new Date();
        const upcoming = events.map(ev=>{
          const start = ev.start.includes('T') ? new Date(ev.start) : new Date(ev.start + 'T00:00');
          return {...ev, _startObj:start};
        }).filter(e => e._startObj >= new Date(now.getTime() - 1000*60*60*24)) // include today and future
          .sort((a,b)=>a._startObj - b._startObj)
          .slice(0,8);

        if(upcoming.length===0){
          const none = document.createElement('div');
          none.style.color = 'var(--muted)';
          none.textContent = 'No upcoming events';
          upcomingList.appendChild(none);
          return;
        }

        upcoming.forEach(ev=>{
          const item = document.createElement('div');
          item.className = 'upcoming-item';
          
          // Different icon for announcements
          const icon = ev.category === 'announcement' ? 'fas fa-bullhorn' : 'fas fa-calendar';
          
          item.innerHTML = `<div style="width:10px;height:40px;border-radius:6px;background:${ev.color};flex-shrink:0"></div>
            <div style="flex:1">
              <div style="font-weight:700">${ev.isImported ? '<i class="fas fa-bullhorn" style="color:#9c27b0;margin-right:4px;"></i>' : ''}${ev.title}</div>
              <div style="font-size:13px;color:#666;margin-top:4px">${formatDisplayDate(ev)}</div>
            </div>`;
          item.addEventListener('click', ()=> openEditModal(ev.id));
          upcomingList.appendChild(item);
        });
      }

      function formatDisplayDate(ev){
        const start = ev.start.includes('T') ? new Date(ev.start) : new Date(ev.start + 'T00:00');
        const opts = { month:'short', day:'numeric' };
        const timePart = ev.start.includes('T') && ev.timeDisplay ? ' • ' + ev.timeDisplay : '';
        return `${start.toLocaleDateString(undefined,opts)}${timePart}`;
      }

      /* ---- modal open/close and CRUD ---- */
      function openAddModal(dateStr){
        editingEventId = null;
        modalTitle.textContent = 'Add Schedule';
        evtTitle.value = '';
        evtDate.value = dateStr || dateToYMD(new Date());
        evtTime.value = '';
        evtCategory.value = CATEGORIES[0].id;
        evtDetails.value = '';
        deleteBtn.style.display = 'none';
        modalBackdrop.classList.add('active');
      }

      function openEditModal(id){
        const ev = events.find(e=>String(e.id)===String(id));
        if(!ev) return;
        editingEventId = ev.id;
        modalTitle.textContent = 'Edit Schedule';
        evtTitle.value = ev.title;
        if(ev.start.includes('T')){
          const parts = ev.start.split('T');
          evtDate.value = parts[0];
          evtTime.value = parts[1].slice(0,5);
        } else {
          evtDate.value = ev.start;
          evtTime.value = '';
        }
        evtCategory.value = ev.category || CATEGORIES[0].id;
        evtDetails.value = ev.details || '';
        deleteBtn.style.display = 'inline-block';
        modalBackdrop.classList.add('active');
      }

      function closeModal(){
        modalBackdrop.classList.remove('active');
      }

      // Save (create or update)
      saveBtn.addEventListener('click', ()=>{
        const title = evtTitle.value.trim();
        const date = evtDate.value;
        const time = evtTime.value;
        const cat = evtCategory.value;
        const details = evtDetails.value.trim();

        if(!title){ alert('Please enter a title'); return; }
        if(!date){ alert('Please select a date'); return; }

        const catObj = CATEGORIES.find(c=>c.id===cat) || CATEGORIES[0];
        const start = datetimeFor(date,time);
        const obj = {
          id: editingEventId || uid(),
          title,
          start,
          category: catObj.id,
          color: catObj.color,
          details,
          timeDisplay: time ? time : ''
        };

        if(editingEventId){
          const idx = events.findIndex(e=>String(e.id)===String(editingEventId));
          if(idx>-1) events[idx] = obj;
        } else {
          events.push(obj);
        }
        saveEvents();
        scheduleAllReminders();
        renderCalendar();
        renderUpcoming();
        closeModal();
      });

      // Delete
      deleteBtn.addEventListener('click', ()=>{
        if(!editingEventId) return;
        if(!confirm('Delete this schedule?')) return;
        
        // Also remove from imported announcements if it's an imported one
        const evToDelete = events.find(e=>String(e.id)===String(editingEventId));
        if (evToDelete && evToDelete.isImported) {
          importedAnnouncements = importedAnnouncements.filter(ann => ann.id !== editingEventId);
          saveImportedAnnouncements();
          renderImportedEvents();
        }
        
        events = events.filter(e=>String(e.id)!==String(editingEventId));
        saveEvents();
        cancelReminder(editingEventId);
        renderCalendar();
        renderUpcoming();
        closeModal();
      });

      cancelBtn.addEventListener('click', closeModal);
      modalBackdrop.addEventListener('click', (e)=>{
        if(e.target === modalBackdrop) closeModal();
      });

      /* ---- navigation ---- */
      prevBtn.addEventListener('click', ()=>{ currentViewDate.setMonth(currentViewDate.getMonth()-1); renderCalendar(); });
      nextBtn.addEventListener('click', ()=>{ currentViewDate.setMonth(currentViewDate.getMonth()+1); renderCalendar(); });
      todayBtn.addEventListener('click', ()=>{ currentViewDate = new Date(); renderCalendar(); });
      addQuickBtn.addEventListener('click', ()=> openAddModal(dateToYMD(new Date())) );

      // keyboard: Esc closes modal
      document.addEventListener('keydown', e => { if(e.key === 'Escape') closeModal(); });

      /* ---- reminders ---- */
      function scheduleAllReminders(){
        // clear existing timers
        Object.keys(reminderTimers).forEach(id => clearTimeout(reminderTimers[id]));
        reminderTimers = {};

        const now = Date.now();
        events.forEach(ev => {
          if(!ev.start) return;
          if(!ev.start.includes('T')) return;
          const evDate = new Date(ev.start);
          const remindAt = evDate.getTime() - REMINDER_OFFSET_MIN*60*1000;
          const ms = remindAt - now;
          if(ms > 0){
            const t = setTimeout(()=> {
              alert(`Reminder: "${ev.title}" at ${ev.timeDisplay || evDate.toLocaleTimeString([], {hour:'2-digit',minute:'2-digit'})}`);
              delete reminderTimers[ev.id];
            }, ms);
            reminderTimers[ev.id] = t;
          }
        });
      }

      function cancelReminder(id){
        if(reminderTimers[id]){ clearTimeout(reminderTimers[id]); delete reminderTimers[id]; }
      }

      /* ---- 🔔 Notification dropdown logic - SAME as Dashboard ---- */
      if (notifBtn && notifDropdown) {
        notifBtn.addEventListener("click", () => {
          notifDropdown.classList.toggle("active");
        });

        // Hide dropdown when clicking outside
        document.addEventListener("click", (e) => {
          if (!notifBtn.contains(e.target) && !notifDropdown.contains(e.target)) {
            notifDropdown.classList.remove("active");
          }
        });
      }

      if (viewAllNotif && notifModal) {
        // View All opens modal
        viewAllNotif.addEventListener("click", () => {
          notifDropdown.classList.remove("active");
          notifModal.classList.add("active");
        });
      }

      if (closeNotifModal) {
        closeNotifModal.addEventListener("click", () => {
          notifModal.classList.remove("active");
        });
      }

      /* ---- persistence & init ---- */
      function init(){
        // populate category select & legend
        evtCategory.innerHTML = '';
        CATEGORIES.forEach(c=>{
          const opt = document.createElement('option');
          opt.value = c.id;
          opt.textContent = c.label;
          evtCategory.appendChild(opt);
        });
        renderLegend();

        loadEvents();
        loadImportedAnnouncements();
        
        // Check for new imports from Activity page
        checkForNewImports();
        
        // normalize events: ensure timeDisplay & color exist
        events = events.map(ev => {
          const cat = CATEGORIES.find(c=>c.id===ev.category) || CATEGORIES[0];
          return {
            id: ev.id || uid(),
            title: ev.title || 'Untitled',
            start: ev.start || dateToYMD(new Date()),
            category: ev.category || cat.id,
            color: ev.color || cat.color,
            details: ev.details || '',
            timeDisplay: ev.timeDisplay || (ev.start && ev.start.includes('T') ? ev.start.split('T')[1].slice(0,5) : ''),
            isImported: ev.isImported || false
          };
        });

        // Schedule reminders (if any) and render UI
        renderCalendar();
        renderUpcoming();
        renderImportedEvents();
        scheduleAllReminders();
      }

      // Quick hook: view all upcoming -> open month of first upcoming
      document.getElementById('viewAllUpcoming').addEventListener('click', (e)=>{
        e.preventDefault();
        if(events.length===0) return;
        const future = events.map(ev=>{
          const dt = ev.start.includes('T') ? new Date(ev.start) : new Date(ev.start + 'T00:00');
          return {...ev,_d:dt};
        }).sort((a,b)=>a._d - b._d);
        if(future.length) {
          currentViewDate = new Date(future[0]._d.getFullYear(), future[0]._d.getMonth(),1);
          renderCalendar();
        }
      });

      // Simulate an imported event for demo purposes
      function addDemoImportedEvent() {
        if (importedAnnouncements.length === 0) {
          const demoDate = new Date();
          demoDate.setDate(demoDate.getDate() + 3); // 3 days from now
          
          const demoEvent = {
            id: uid(),
            title: 'Faculty Development Program',
            date: dateToYMD(demoDate),
            description: 'All teaching staff are invited to join the upcoming seminar',
            importedDate: new Date().toISOString(),
            source: 'activity'
          };
          
          importedAnnouncements.push(demoEvent);
          saveImportedAnnouncements();
          
          const calendarEvent = {
            id: demoEvent.id,
            title: demoEvent.title,
            start: demoEvent.date + 'T09:00',
            category: 'announcement',
            color: '#9c27b0',
            details: demoEvent.description,
            timeDisplay: '09:00',
            isImported: true
          };
          
          events.push(calendarEvent);
          saveEvents();
          
          renderCalendar();
          renderUpcoming();
          renderImportedEvents();
          scheduleAllReminders();
        }
      }

      // initial render
      init();
      
      // Add demo event if none exist (for first-time users)
      setTimeout(addDemoImportedEvent, 500);

      // expose small debugging on console
      window.__calendar_events = events;
      window.__imported_announcements = importedAnnouncements;
      window.__calendar_refresh = function(){ 
        loadEvents(); 
        loadImportedAnnouncements();
        renderCalendar(); 
        renderUpcoming(); 
        renderImportedEvents();
        scheduleAllReminders(); 
      };
    })();