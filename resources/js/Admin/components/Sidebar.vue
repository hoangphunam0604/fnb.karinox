<script setup>
import { ref } from 'vue';

const activeIndex = ref(null);

const menuItems = ref([
  {
    title: 'Dashboard',
    subItems: [
      { href: 'admin.dashboard', text: 'Dashboard' },
    ],
  }, {
    title: 'Chi nhánh',
    subItems: [
      { href: 'admin.branches.index', text: 'Chi nhánh' },
      { href: 'admin.branches.create', text: 'Thêm chi nhánh' },
    ],
  },
]);

const toggleMenu = (index) => {
  activeIndex.value = activeIndex.value === index ? null : index;
};
// Hiệu ứng mở rộng chiều cao ul (slide)
const beforeEnter = (el) => {
  el.style.height = '0';
};

const enter = (el) => {
  el.style.height = el.scrollHeight + 'px';
  el.style.transition = 'height 0.3s ease';
};

const leave = (el) => {
  el.style.height = '0';
  el.style.transition = 'height 0.3s ease';
};
</script>
<template>
  <div class="deznav">
    <div class="deznav-scroll">
      <ul class="metismenu" id="menu">
        <li v-for="(item, index) in menuItems" :key="index" :class="{ 'mm-active': activeIndex === index }">
          <a class="has-arrow ai-icon" href="javascript:void(0)" @click="toggleMenu(index)">
            <i class="flaticon-381-networking"></i>
            <span class="nav-text">{{ item.title }}</span>
          </a>
          <transition name="slide-toggle" @before-enter="beforeEnter" @enter="enter" @leave="leave">

            <ul class="mm-collapse" :class="{ 'mm-show': activeIndex === index }">
              <li v-for="(subItem, subIndex) in item.subItems" :key="subIndex">
                <a :href="route(subItem.href)">{{ subItem.text }}</a>
              </li>
            </ul>
          </transition>
        </li>
      </ul>
      <div class="copyright">
        <p>Tixia Ticketing Admin Dashboard <br />© 2021 All Rights Reserved</p>
      </div>
    </div>
  </div>
</template>
<style>
.slide-toggle-enter-active,
.slide-toggle-leave-active {
  transition: height 0.3s ease;
}

.slide-toggle-enter-from,
.slide-toggle-leave-to {
  height: 0;
}
</style>