import { config } from "../function";

let Sidebar = {};
export default Sidebar = {
    compact : function(){
      let toggle = document.querySelectorAll('.sidebar-compact-toggle');
      let parent = document.querySelector('.nk-sidebar');
      let body = parent && parent.querySelector('.nk-sidebar-body');
      toggle && toggle.forEach(item => {
        if (item.dataset.sidebarCompactBound === '1') {
          return;
        }

        item.dataset.sidebarCompactBound = '1';
        item.addEventListener("click", function(e){
          e.preventDefault();
          item.classList.toggle('compact-active');
          parent.classList.toggle('is-compact');
          !parent.classList.contains('is-compact') && parent.classList.remove('has-hover');
        });
      })
      if (body && body.dataset.sidebarHoverBound !== '1') {
        body.dataset.sidebarHoverBound = '1';
        body.addEventListener('mouseenter', function () {
            parent.classList.contains('is-compact') && parent.classList.add('has-hover');
        });
        body.addEventListener('mouseleave', function () {
            parent.classList.contains('is-compact') && parent.classList.remove('has-hover');
        });
      }
    },
    
    toggle: function(){
      let toggle = document.querySelectorAll('.sidebar-toggle');
      let parent = document.querySelector('.nk-sidebar');
      toggle.forEach(item => {
        if (item.dataset.sidebarToggleBound === '1') {
          return;
        }

        item.dataset.sidebarToggleBound = '1';
        item.addEventListener("click", function(e){
          e.preventDefault();
          if (! parent) {
            return;
          }

          if (window.innerWidth >= config.break.xl) {
            parent.classList.remove('sidebar-visible');
            document.body.classList.remove('overflow-hidden');

            return;
          }

          const isSidebarVisible = parent.classList.toggle('sidebar-visible');
          toggle.forEach((button) => {
            button.classList.toggle('active', isSidebarVisible);
          });
          document.body.classList.toggle('overflow-hidden', isSidebarVisible);
        });
      });

      if (parent && parent.dataset.sidebarMenuCloseBound !== '1') {
        parent.dataset.sidebarMenuCloseBound = '1';
        parent.addEventListener('click', function (event) {
          const link = event.target.closest('a');
          if (!link) {
            return;
          }

          if (
            link.classList.contains('sidebar-toggle') ||
            link.classList.contains('sidebar-compact-toggle') ||
            link.classList.contains('nk-menu-toggle')
          ) {
            return;
          }

          if (window.innerWidth >= config.break.xl) {
            return;
          }

          parent.classList.remove('sidebar-visible');
          document.body.classList.remove('overflow-hidden');
          toggle.forEach((button) => {
            button.classList.remove('active');
          });
        });
      }

      if (window.innerWidth >= config.break.xl) {
        parent && parent.classList.remove('sidebar-visible');
        document.body.classList.remove('overflow-hidden');
      }
    },

    page_resize: function(){
      let toggle = document.querySelectorAll('.sidebar-toggle');
      let parent = document.querySelector('.nk-sidebar');
      if (window.innerWidth > config.break.xl) {
        toggle.forEach(item => {
          item.classList.remove('active');
        })
        parent && parent.classList.remove('sidebar-visible');
        document.body.classList.remove('overflow-hidden');
      }
    }
}
